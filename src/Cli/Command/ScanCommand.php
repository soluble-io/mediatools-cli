<?php

declare(strict_types=1);

namespace Soluble\MediaTools\Cli\Command;

use Soluble\MediaTools\Video\Exception\InfoReaderExceptionInterface;
use Soluble\MediaTools\Video\VideoInfoReaderInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class ScanCommand extends Command
{
    /**
     * @var VideoInfoReaderInterface
     */
    private $reader;

    /** @var string[] */
    private $supportedVideoExtensions = [
        'mov',
        'mp4',
        'm4v',
        'mkv',
        'flv',
        'webm',
    ];

    public function __construct(VideoInfoReaderInterface $videoInfoReader)
    {
        $this->reader = $videoInfoReader;
        parent::__construct();
    }

    /**
     * Configures the command.
     */
    protected function configure(): void
    {
        $this
            ->setName('scan:videos')
            ->setDescription('Scan for video')
            ->setDefinition(
                new InputDefinition([
                    new InputOption('dir', 'd', InputOption::VALUE_REQUIRED),
                ])
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$input->hasOption('dir')) {
            throw new \Exception('Missing dir argument, use <command> <dir>');
        }
        $videoPath = $input->hasOption('dir') ? $input->getOption('dir') : '';
        if (!is_string($videoPath) || !is_dir($videoPath)) {
            throw new \Exception(sprintf(
                'Video dir %s does not exists',
                is_string($videoPath) ? $videoPath : ''
            ));
        }

        $output->writeln('Hello');

        // Get the videos in path

        $videos = $this->getVideoFiles($videoPath);

        $progressBar = new ProgressBar($output, count($videos));
        $progressBar->start();

        $rows = [];

        /** @var \SplFileInfo $video */
        foreach ($videos as $video) {
            $videoFile = $video->getPathname();

            try {
                $info    = $this->reader->getInfo($videoFile);
                $vStream = $info->getVideoStreams()->getFirst();

                $pixFmt = $vStream->getPixFmt();

                $rows[] = [
                    $video->getBasename(),
                    sprintf('%sx%s', $vStream->getWidth(), $vStream->getHeight()),
                    $info->getDuration(),
                    $vStream->getBitRate(),
                    $vStream->getCodecName(),
                    $pixFmt,
                    filesize($videoFile),
                ];
            } catch (InfoReaderExceptionInterface $e) {
                $output->writeln('Failed');
            }

            $progressBar->advance();
        }

        $output->writeln('');

        $table = new Table($output);
        $table->setHeaders([
            'file',
            'size',
            'duration',
            'bitrate',
            'codec',
            'fmt',
            'filesize',
        ]);
        $table->setRows($rows ?? []);
        $table->render();

        $output->writeln("\nFinished");

        return 1;
    }

    /**
     * @param string $videoPath
     *
     * @return array<\SplFileInfo>
     */
    public function getVideoFiles(string $videoPath): array
    {
        $files = (new Finder())->files()
            ->in($videoPath)
            ->name(sprintf(
                '/\.(%s)$/',
                implode('|', $this->supportedVideoExtensions)
            ));

        $videos = [];

        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            $videos[] = $file;
        }

        return $videos;
    }
}
