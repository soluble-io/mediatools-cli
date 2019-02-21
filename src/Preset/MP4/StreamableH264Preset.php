<?php

declare(strict_types=1);

namespace Soluble\MediaTools\Preset\MP4;

use Soluble\MediaTools\Preset\PresetInterface;
use Soluble\MediaTools\Video\VideoAnalyzerInterface;
use Soluble\MediaTools\Video\VideoConverterInterface;
use Soluble\MediaTools\Video\VideoConvertParams;
use Soluble\MediaTools\Video\VideoInfoReaderInterface;

class StreamableH264Preset implements PresetInterface
{
    /** @var VideoInfoReaderInterface */
    private $reader;

    /** @var VideoConverterInterface */
    private $converter;

    /** @var VideoAnalyzerInterface */
    private $analyzer;

    public function __construct(VideoInfoReaderInterface $reader, VideoConverterInterface $converter, VideoAnalyzerInterface $analyzer)
    {
        $this->converter = $converter;
        $this->reader    = $reader;
        $this->analyzer  = $analyzer;
    }

    public function convert(string $file, ?int $width = null, ?int $height = null): void
    {
        $info = $this->reader->getInfo($file);

        $vStream = $info->getVideoStreams()->getFirst();
        $aStream = $info->getAudioStreams()->getFirst();

        $width  = $width ?? $vStream->getWidth();
        $height = $height ?? $vStream->getHeight();

        $params = $this->getParams($width, $height);

        $outputFile = sprintf('%s/%s.new.mp4', sys_get_temp_dir(), basename($file));

        $cb = function ($stderr, $stdin): void {
            echo '.';
        };

        $this->converter->convert($file, $outputFile, $params, $cb);
    }

    public function getParams(int $width, int $height): VideoConvertParams
    {
        $params = (new VideoConvertParams())
            ->withVideoCodec('libx264')
            ->withStreamable(true)
            ->withCrf(24);

        return $params;
    }
}
