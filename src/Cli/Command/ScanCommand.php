<?php

declare(strict_types=1);

namespace Soluble\MediaTools\Cli\Command;

use ScriptFUSION\Byte\ByteFormatter;
use ScriptFUSION\Byte\Unit\SymbolDecorator;
use Soluble\MediaTools\Video\Exception\InfoReaderExceptionInterface;
use Soluble\MediaTools\Video\SeekTime;
use Soluble\MediaTools\Video\VideoInfoReaderInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Helper\TableStyle;
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

        $output->writeln(sprintf('Scanning %s', $videoPath));

        // Get the videos in path
        $videos = $this->getVideoFiles($videoPath);

        $progressBar = new ProgressBar($output, count($videos));
        $progressBar->start();

        $bitRateFormatter = new ByteFormatter();
        //$bitRateFormatter->setUnitDecorator(new SymbolDecorator(SymbolDecorator::SUFFIX_NONE));

        $sizeFormatter = new ByteFormatter();

        $rows      = [];
        $warnings  = [];
        $totalSize = 0;

        /** @var \SplFileInfo $video */
        foreach ($videos as $video) {
            $videoFile = $video->getPathname();
            try {
                $info     = $this->reader->getInfo($videoFile);
                $vStream  = $info->getVideoStreams()->getFirst();
                $pixFmt   = $vStream->getPixFmt();
                $bitRate  = $vStream->getBitRate();
                $fileSize = (int) filesize($videoFile);
                $row      = [
                    $video,
                    preg_replace('/\.([0-9])+$/', '', SeekTime::convertSecondsToHMSs(round($info->getDuration(), 1))),
                    $vStream->getCodecName(),
                    sprintf('%sx%s', $vStream->getWidth(), $vStream->getHeight()),
                    ($bitRate > 0 ? $bitRateFormatter->format((int) $bitRate) . '/s' : ''),
                    $sizeFormatter->format($fileSize),
                    $pixFmt,
                ];
                $rows[] = $row;
                $totalSize += $fileSize;
            } catch (InfoReaderExceptionInterface $e) {
                $warnings[] = [$videoFile];
            }

            $progressBar->advance();
        }

        $output->writeln('');

        $table = new Table($output);

        $rightAlignstyle = new TableStyle();
        $rightAlignstyle->setPadType(STR_PAD_LEFT);

        //$table->setStyle($tableStyle);
        $table->setStyle('box');
        $table->setHeaders([
            'file',
            'duration',
            'codec',
            'size',
            'bitrate',
            'filesize',
            'pix_fmt',
        ]);

        $previousPath = null;
        $first        = true;

        foreach ($rows as $row) {
            /** @var \SplFileInfo $file */
            $file   = $row[0];
            $row[0] = $file->getBasename();
            if ($previousPath !== $file->getPath()) {
                if (!$first) {
                    $table->addRow(new TableSeparator(['colspan' => count($row)]));
                }
                //$table->addRow([new TableCell(sprintf('<fg=yellow>%s</>', $file->getPath()), ['colspan' => count($row)])]);
                $table->addRow([new TableCell(sprintf('<fg=yellow>%s</>', $file->getPath()))]);
                $table->addRow(new TableSeparator(['colspan' => count($row)]));
                $previousPath = $file->getPath();
            }
            $table->addRow($row);
            $first = false;
        }
        foreach ($colIndexes = [1, 2, 3, 4, 5, 6] as $idx) {
            $table->setColumnStyle($idx, $rightAlignstyle);
        }
        $table->addRow(new TableSeparator());
        $table->addRow(['<fg=cyan>Total</>', '', '', '', '', sprintf('<fg=cyan>%s</>', $sizeFormatter->format($totalSize))]);

        $table->render();

        // display warnings
        if (count($warnings) > 0) {
            $table = new Table($output);
            $table->setHeaders([
                'Unsupported files'
            ]);
            $table->setStyle('box');
            $table->setRows($warnings);
            $table->render();
        }

        $output->writeln("\nFinished");

        return 1;
    }

    private function outputTable(array $rows): void
    {
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
