<?php

declare(strict_types=1);

/**
 * @see       https://github.com/soluble-io/soluble-mediatools-cli for the canonical repository
 *
 * @copyright Copyright (c) 2018-2019 Sébastien Vanvelthem. (https://github.com/belgattitude)
 * @license   https://github.com/soluble-io/soluble-mediatools-cli/blob/master/LICENSE.md MIT
 */

namespace Soluble\MediaTools\Cli\Command;

use Soluble\MediaTools\Cli\FileSystem\DirectoryScanner;
use Soluble\MediaTools\Cli\Media\FileExtensions;
use Soluble\MediaTools\Cli\Media\MediaScanner;
use Soluble\MediaTools\Cli\Service\MediaToolsServiceInterface;
use Soluble\MediaTools\Cli\Util\FFMpegProgress;
use Soluble\MediaTools\Common\Exception\ProcessException;
use Soluble\MediaTools\Preset\PresetInterface;
use Soluble\MediaTools\Preset\PresetLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Webmozart\Assert\Assert;

class ConvertDirCommand extends Command
{
    /** @var MediaToolsServiceInterface */
    private $mediaTools;

    /** @var PresetLoader */
    private $presetLoader;

    /** @var string[] */
    private $supportedVideoExtensions;

    public function __construct(MediaToolsServiceInterface $mediaTools, PresetLoader $presetLoader)
    {
        $this->mediaTools               = $mediaTools;
        $this->presetLoader             = $presetLoader;
        $this->supportedVideoExtensions = (new FileExtensions())->getMediaExtensions();
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('convert:directory')
            ->setDescription('Convert all media files in a directory using a preset')
            ->setDefinition(
                new InputDefinition([
                    new InputOption('dir', ['d'], InputOption::VALUE_REQUIRED, 'Input directory to scan for medias'),
                    new InputOption('preset', ['p'], InputOption::VALUE_REQUIRED, 'Conversion preset to use'),
                    new InputOption('exts', ['e', 'extensions'], InputOption::VALUE_OPTIONAL, 'File extensions to process (ie. m4v,mp4,mov)'),
                    new InputOption('output', ['o', 'out'], InputOption::VALUE_REQUIRED, 'Output directory'),
                    new InputOption('recursive', 'r', InputOption::VALUE_NONE, 'Recursive mode'),
                    new InputOption('no-interaction', 'n', InputOption::VALUE_NONE, 'No interaction, set yes to all'),
                ])
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');

        // ########################
        // Step 1: Check directory
        // ########################

        $directory = $input->getOption('dir');
        Assert::stringNotEmpty($directory);
        Assert::directory($directory);

        // ########################
        // Step 2: Init preset
        // ########################

        Assert::stringNotEmpty($input->getOption('preset'));
        $preset = $this->getPreset($input->getOption('preset'));

        // ########################
        // Step 3: Output dir
        // ########################

        if ($input->getOption('output') !== null) {
            $outputDir = $input->getOption('output');
            Assert::directory($outputDir);
            Assert::writable($outputDir);
        } else {
            $outputDir = $directory;
        }
        Assert::stringNotEmpty($outputDir);

        // ########################
        // Step 4: Extensions
        // ########################

        if ($input->getOption('exts') !== null) {
            $tmp = $input->getOption('exts');
            Assert::stringNotEmpty($tmp);
            $exts = array_filter(
                array_map(
                    'trim',
                    explode(',', $tmp)
                )
            );
            Assert::minCount($exts, 1);
        } else {
            $exts = FileExtensions::BUILTIN_EXTENSIONS;
        }

        $recursive = $input->getOption('recursive') === true;

        $no_interaction = $input->getOption('no-interaction') === true;

        // ########################
        // Step 5: Scanning dir
        // ########################

        $output->writeln(sprintf('* Scanning %s for media files...', $directory));

        // Get the videos in path
        $files = (new DirectoryScanner())->findFiles($directory, $exts, $recursive);

        $output->writeln('* Reading metadata...');

        $readProgress = new ProgressBar($output, count($files));
        $readProgress->start();

        $medias = (new MediaScanner($this->mediaTools->getReader()))->getMedias($files, function () use ($readProgress): void {
            $readProgress->advance();
        });

        $readProgress->finish();

        // Ask confirmation
        ScanCommand::renderMediaInTable($output, $medias['rows'], $medias['totalSize']);

        $question = new ConfirmationQuestion('Convert files ?', false);

        if (!$no_interaction && !$helper->ask($input, $output, $question)) {
            return 0;
        }

        $converter = $this->mediaTools->getConverter();

        /* @var \SplFileInfo $file */
        foreach ($medias['rows'] as $row) {
            $file = $row['file'];
            try {
                $outputFile = sprintf(
                    '%s/%s%s',
                    $outputDir,
                    $file->getBasename($file->getExtension()),
                    $preset->getFileExtension()
                );

                $tmpFile = sprintf('%s.tmp', $outputFile);

                if (realpath($outputFile) === realpath((string) $file)) {
                    throw new \RuntimeException(sprintf('Conversion error, input and output files are the same: %s', $outputFile));
                }

                if (!file_exists($outputFile)) {
                    $progress    = new FFMpegProgress();
                    $progressBar = new ProgressBar($output, (int) $row['total_time']);
                    $params      = $preset->getParams($file->getPathname());

                    $output->writeln(sprintf('Convert %s to %s', $file->getBasename(), $outputFile));

                    $converter->convert((string) $file, $tmpFile, $params, function ($stdOut, $stdErr) use ($progressBar, $progress): void {
                        $info = $progress->getProgress($stdErr);
                        if (!is_array($info)) {
                            return;
                        }
                        $progressBar->setProgress((int) $info['time']);
                    });
                    $progressBar->finish();
                    $output->writeln('');
                    $success = rename($tmpFile, $outputFile);
                    if (!$success) {
                        throw new \RuntimeException(sprintf(
                            'Cannot rename temp file %s to %s',
                            basename($tmpFile),
                            $outputFile
                        ));
                    }

                    $output->writeln(sprintf('<fg=green>- Converted:</> %s.', $file));
                } else {
                    $output->writeln(sprintf('<fg=yellow>- Skipped:</> %s : Output file already exists (%s).', (string) $file, $outputFile));
                }
            } catch (ProcessException $e) {
                $output->writeln(sprintf('<fg=red>- Skipped:</> %s : Not a valid media file.', (string) $file));
            }
        }

        $output->writeln('');

        return 0;
    }

    private function grepFrame(string $line): int
    {
        return 1;
    }

    private function getPreset(string $presetName): PresetInterface
    {
        return $this->presetLoader->getPreset($presetName);
    }
}
