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
use Soluble\MediaTools\Video\VideoAnalyzerInterface;
use Soluble\MediaTools\Video\VideoConverterInterface;
use Soluble\MediaTools\Video\VideoInfoReaderInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConvertDirCommand extends Command
{
    /** @var VideoInfoReaderInterface */
    protected $videoInfoReader;

    /** @var VideoAnalyzerInterface */
    protected $videoAnalyzer;

    /** @var VideoConverterInterface */
    protected $videoConverter;

    /** @var string[] */
    protected $supportedVideoExtensions = [
        'mov',
        'mp4',
        'mkv',
        'flv',
        'webm',
    ];

    public function __construct(VideoInfoReaderInterface $videoInfoReader, VideoAnalyzerInterface $videoAnalyzer, VideoConverterInterface $videoConverter)
    {
        $this->videoInfoReader = $videoInfoReader;
        $this->videoAnalyzer   = $videoAnalyzer;
        $this->videoConverter  = $videoConverter;
        parent::__construct();
    }

    /**
     * Configures the command.
     */
    protected function configure(): void
    {
        $this
            ->setName('convert:directory')
            ->setDescription('Convert, transcode all media files in a directory')
            ->setDefinition(
                new InputDefinition([
                    new InputOption('dir', 'd', InputOption::VALUE_REQUIRED),
                ])
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$input->hasOption('dir')) {
            throw new \InvalidArgumentException('Missing dir argument, use <command> <dir>');
        }
        $directory = $input->hasOption('dir') ? $input->getOption('dir') : null;
        if (!is_string($directory) || !is_dir($directory)) {
            throw new \InvalidArgumentException(sprintf(
                'Directory %s does not exists',
                is_string($directory) ? $directory : json_encode($directory)
            ));
        }

        $output->writeln(sprintf('* Scanning %s for media files...', $directory));

        // Get the videos in path
        $files = (new DirectoryScanner())->findFiles($directory, $this->supportedVideoExtensions);

        $output->writeln('* Reading metadata...');

        $progressBar = new ProgressBar($output, count($files));
        $progressBar->start();

        /* @var \SplFileInfo $video */
        foreach ($files as $file) {
            $progressBar->advance();
        }

        $progressBar->finish();
        $output->writeln('');

        return 0;
    }
}