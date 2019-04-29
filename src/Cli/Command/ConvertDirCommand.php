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
use Soluble\MediaTools\Cli\Service\MediaToolsServiceInterface;
use Soluble\MediaTools\Common\Exception\ProcessException;
use Soluble\MediaTools\Preset\PresetInterface;
use Soluble\MediaTools\Preset\PresetLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\Assert\Assert;

class ConvertDirCommand extends Command
{
    /** @var MediaToolsServiceInterface */
    private $mediaTools;

    /**
     * @var PresetLoader
     */
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
            ->setDescription('Convert, transcode all media files in a directory')
            ->setDefinition(
                new InputDefinition([
                    new InputOption('dir', 'd', InputOption::VALUE_REQUIRED, 'Directory to convert'),
                    new InputOption('preset', 'p', InputOption::VALUE_REQUIRED, 'Conversion preset to use'),
                ])
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
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
        // Step 3: Scanning dir
        // ########################

        $output->writeln(sprintf('* Scanning %s for media files...', $directory));

        // Get the videos in path
        $files = (new DirectoryScanner())->findFiles($directory, ['mp4']);

        $output->writeln('* Reading metadata...');

        $progressBar = new ProgressBar($output, count($files));
        //$progressBar->start();

        $converter = $this->mediaTools->getConverter();

        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            try {
                $params = $preset->getParams($file->getPathname());

                $outputFile = sprintf('%s%s', (string) $file, '.mov');

                if (!file_exists($outputFile)) {
                    $converter->convert((string) $file, $outputFile, $params, function ($stdOut, $stdErr) use ($output) {
                        $output->write($stdErr);
                    });

                    $output->writeln(sprintf('<fg=green>- Converted:</> %s.', $file));
                } else {
                    $output->writeln(sprintf('<fg=yellow>- Skipped:</> %s : Output file already exists.', $file));
                }
            } catch (ProcessException $e) {
                $output->writeln(sprintf('<fg=red>- Skipped:</> %s : Not a valid media file.', $file));
            }

            //$progressBar->advance();
        }

        $progressBar->finish();
        $output->writeln('');

        return 0;
    }

    private function getPreset(string $presetName): PresetInterface
    {
        return $this->presetLoader->getPreset($presetName);
    }
}
