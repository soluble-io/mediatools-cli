<?php

declare(strict_types=1);

/**
 * @see       https://github.com/soluble-io/soluble-mediatools-cli for the canonical repository
 *
 * @copyright Copyright (c) 2018-2019 Sébastien Vanvelthem. (https://github.com/belgattitude)
 * @license   https://github.com/soluble-io/soluble-mediatools-cli/blob/master/LICENSE.md MIT
 */

namespace Soluble\MediaTools\Cli\Command;

use ScriptFUSION\Byte\ByteFormatter;
use Soluble\MediaTools\Cli\Exception\MissingFFProbeBinaryException;
use Soluble\MediaTools\Cli\FileSystem\DirectoryScanner;
use Soluble\MediaTools\Video\Exception as VideoException;
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

class ScanCommand extends Command
{
    /** @var VideoInfoReaderInterface */
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$input->hasOption('dir')) {
            throw new \InvalidArgumentException('Missing dir argument, use <command> <dir>');
        }
        $videoPath = $input->hasOption('dir') ? $input->getOption('dir') : null;
        if (!is_string($videoPath) || !is_dir($videoPath)) {
            throw new \InvalidArgumentException(sprintf(
                'Video dir %s does not exists',
                is_string($videoPath) ? $videoPath : json_encode($videoPath)
            ));
        }

        $output->writeln(sprintf('* Scanning %s for files...', $videoPath));

        // Get the videos in path
        $videos = (new DirectoryScanner())->findFiles($videoPath, $this->supportedVideoExtensions);

        $output->writeln('* Reading metadata...');

        $progressBar = new ProgressBar($output, count($videos));
        $progressBar->start();

        $bitRateFormatter = new ByteFormatter();
        $sizeFormatter    = new ByteFormatter();

        $rows      = [];
        $warnings  = [];
        $totalSize = 0;

        /** @var \SplFileInfo $video */
        foreach ($videos as $video) {
            $videoFile = $video->getPathname();
            try {
                $info     = $this->reader->getInfo($videoFile);
                $vStream  = $info->getVideoStreams()->getFirst();
                $aStream  = $info->getAudioStreams()->getFirst();
                $pixFmt   = $vStream->getPixFmt();
                $bitRate  = $vStream->getBitRate();
                $fileSize = $video->getSize();

                $fps             = $vStream->getRFrameRate() ?? '';
                [$frames, $base] = explode('/', $fps);

                $fps2 = number_format($frames / $base, 3, '.', '');

                try {
                    $fps3 = number_format($vStream->getNbFrames() ?? 0 / $vStream->getDuration() ?? 1, 3, '.', '');
                } catch (\Throwable $e) {
                    $fps3 = '?';
                }
                //$fps2 = $vStream->getNbFrames() > 0 ? $vStream->getNbFrames() / $vStream->getDurationTs() : null;

                $row = [
                    $video,
                    preg_replace('/\.([0-9])+$/', '', SeekTime::convertSecondsToHMSs(round($info->getDuration(), 1))),
                    sprintf('%s/%s', $vStream->getCodecName(), $aStream->getCodecName()),
                    sprintf('%sx%s', $vStream->getWidth(), $vStream->getHeight()),
                    ($bitRate > 0 ? $bitRateFormatter->format((int) $bitRate) . '/s' : ''),
                    $fps . ' - ' . $fps2 . ' - ' . $fps3,
                    $sizeFormatter->format($fileSize),
                    $pixFmt,
                ];
                $rows[] = $row;
                $totalSize += $fileSize;
            } catch (VideoException\MissingFFProbeBinaryException $e) {
                throw new MissingFFProbeBinaryException('Unable to run ffprobe binary, check your config and ensure it\'s properly installed');
            } catch (VideoException\InfoReaderExceptionInterface $e) {
                $warnings[] = [$videoFile];
            }

            $progressBar->advance();
        }
        $progressBar->finish();

        $output->writeln('');
        $output->writeln('* Available media files:');

        $this->renderResultsTable($output, $rows, $totalSize);

        // display warnings
        if (count($warnings) > 0) {
            $output->writeln('* The following files were not detected as valid medias:');
            $table = new Table($output);
            $table->setHeaders([
                'Unsupported files',
            ]);
            $table->setStyle('box-double');
            $table->setRows($warnings);
            $table->render();
        }

        $output->writeln('');

        return 0;
    }

    private function renderResultsTable(OutputInterface $output, array $rows, int $totalSize, array $columns = []): void
    {
        $sizeFormatter = new ByteFormatter();

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
            'fps',
            'filesize',
            'pix_fmt',
        ]);

        foreach ($colIndexes = [1, 2, 3, 4, 5, 6] as $idx) {
            $table->setColumnStyle($idx, $rightAlignstyle);
        }

        $previousPath = null;
        $first        = true;

        foreach ($rows as $row) {
            /** @var \SplFileInfo $file */
            $file     = $row[0];
            $fileName = $file->getBasename();
            $fileName = mb_strlen($fileName) > 30 ? mb_substr($file->getBasename(), 0, 30) . '[...].' . $file->getExtension() : $fileName;
            $row[0]   = $fileName;
            if ($previousPath !== $file->getPath()) {
                if (!$first) {
                    $table->addRow(new TableSeparator(['colspan' => count($row)]));
                }
                $table->addRow([new TableCell(sprintf('<fg=yellow>%s</>', $file->getPath()), ['colspan' => count($row)])]);
                $table->addRow(new TableSeparator(['colspan' => count($row)]));
                $previousPath = $file->getPath();
            }
            $table->addRow($row);
            $first = false;
        }

        $table->addRow(new TableSeparator());
        $table->addRow(['<fg=cyan>Total</>', '', '', '', '', sprintf('<fg=cyan>%s</>', $sizeFormatter->format($totalSize))]);

        $table->render();
    }
}
