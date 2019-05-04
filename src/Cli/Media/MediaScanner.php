<?php

declare(strict_types=1);

namespace Soluble\MediaTools\Cli\Media;

use ScriptFUSION\Byte\ByteFormatter;
use Soluble\MediaTools\Cli\Exception\MissingFFProbeBinaryException;
use Soluble\MediaTools\Video\Exception as VideoException;
use Soluble\MediaTools\Video\SeekTime;
use Soluble\MediaTools\Video\VideoInfoReaderInterface;
use SplFileInfo;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Output\OutputInterface;

class MediaScanner
{
    /**
     * @var VideoInfoReaderInterface
     */
    private $reader;

    public function __construct(VideoInfoReaderInterface $videoInfoReader)
    {
        $this->reader = $videoInfoReader;
    }

    /**
     * @param SplFileInfo[] $files
     */
    public function getMedias(array $files, ?callable $callback): array
    {
        $bitRateFormatter = new ByteFormatter();
        $sizeFormatter    = new ByteFormatter();

        $rows      = [];
        $warnings  = [];
        $totalSize = 0;

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            $videoFile = $file->getPathname();
            try {
                $info     = $this->reader->getInfo($videoFile);
                $vStream  = $info->getVideoStreams()->getFirst();
                $aStream  = $info->getAudioStreams()->getFirst();
                $pixFmt   = $vStream->getPixFmt();
                $bitRate  = $vStream->getBitRate();
                $fileSize = $file->getSize();

                $fps = (string) ($vStream->getFps(0) ?? '');

                $row = [
                    'video'      => $file,
                    'duration'   => preg_replace('/\.([0-9])+$/', '', SeekTime::convertSecondsToHMSs(round($info->getDuration(), 1))),
                    'codec'      => sprintf('%s/%s', $vStream->getCodecName(), $aStream->getCodecName()),
                    'resolution' => sprintf(
                        '%sx%s',
                        $vStream->getWidth(),
                        $vStream->getHeight()
                    ),
                    'fps'     => $fps,
                    'bitrate' => ($bitRate > 0 ? $bitRateFormatter->format((int) $bitRate) . '/s' : ''),
                    'size'    => $sizeFormatter->format($fileSize),
                    'pixFmt'  => $pixFmt,
                ];
                $rows[] = $row;
                $totalSize += $fileSize;
            } catch (VideoException\MissingFFProbeBinaryException $e) {
                throw new MissingFFProbeBinaryException('Unable to run ffprobe binary, check your config and ensure it\'s properly installed');
            } catch (VideoException\InfoReaderExceptionInterface $e) {
                $warnings[] = [$videoFile];
            }

            if ($callback !== null) {
                $callback();
            }
        }

        return [
            'rows'      => $rows,
            'totalSize' => $totalSize,
            'warnings'  => $warnings
        ];
    }


}
