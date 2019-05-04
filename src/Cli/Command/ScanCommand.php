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
use Soluble\MediaTools\Cli\Exception\InvalidArgumentException;
use Soluble\MediaTools\Cli\FileSystem\DirectoryScanner;
use Soluble\MediaTools\Cli\Media\FileExtensions;
use Soluble\MediaTools\Cli\Media\MediaScanner;
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
    private $supportedVideoExtensions;

    public function __construct(VideoInfoReaderInterface $videoInfoReader)
    {
        $this->reader                   = $videoInfoReader;
        $this->supportedVideoExtensions = (new FileExtensions())->getMediaExtensions();
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
                    new InputOption('dir', 'd', InputOption::VALUE_REQUIRED, 'Directory to scan'),
                    new InputOption('recursive', 'r', InputOption::VALUE_NONE, 'Recursive mode'),
                ])
            );
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$input->hasOption('dir')) {
            throw new InvalidArgumentException('Missing dir argument, use <command> <dir>');
        }

        $videoPath = $input->getOption('dir');
        if (!is_string($videoPath) || !is_dir($videoPath)) {
            throw new InvalidArgumentException(sprintf(
                'Video dir %s does not exists',
                is_string($videoPath) ? $videoPath : json_encode($videoPath)
            ));
        }

        $recursive = $input->getOption('recursive') === true;

        $output->writeln(sprintf('* Scanning %s for files...', $videoPath));

        // Get the videos in path
        $videos = (new DirectoryScanner())->findFiles($videoPath, $this->supportedVideoExtensions, $recursive);

        $output->writeln('* Reading metadata...');

        $progressBar = new ProgressBar($output, count($videos));
        $progressBar->start();

        $medias = (new MediaScanner($this->reader))->getMedias($videos, function () use ($progressBar): void {
            $progressBar->advance();
        });

        $progressBar->finish();

        $output->writeln('');
        $output->writeln('* Available media files:');

        self::renderMediaInTable($output, $medias['rows'], $medias['totalSize']);

        // display warnings

        if (count($medias['warnings']) > 0) {
            $output->writeln('* The following files were not detected as valid medias:');
            $table = new Table($output);
            $table->setHeaders([
                'Unsupported files',
            ]);
            $table->setStyle('box-double');
            $table->setRows($medias['warnings']);
            $table->render();
        }

        $output->writeln('');

        return 0;
    }

    public static function renderMediaInTable(OutputInterface $output, array $rows, int $totalSize, array $columns = []): void
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
            'fps',
            'bitrate',
            'filesize',
            'pix_fmt',
        ]);

        foreach ($colIndexes = [1, 2, 3, 4, 5, 6] as $idx) {
            $table->setColumnStyle($idx, $rightAlignstyle);
        }

        $previousPath = null;
        $first        = true;

        foreach ($rows as $idx => $row) {
            /** @var \SplFileInfo $file */
            $file         = $row['file'];
            $fileName     = $file->getBasename();
            $fileName     = mb_strlen($fileName) > 30 ? mb_substr($file->getBasename(), 0, 30) . '[...].' . $file->getExtension() : $fileName;
            $row['video'] = $fileName;
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
