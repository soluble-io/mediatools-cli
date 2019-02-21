<?php

declare(strict_types=1);

namespace Soluble\MediaTools\Preset\WebM;

use Soluble\MediaTools\Preset\PresetInterface;
use Soluble\MediaTools\Video\VideoAnalyzerInterface;
use Soluble\MediaTools\Video\VideoConverterInterface;
use Soluble\MediaTools\Video\VideoConvertParams;
use Soluble\MediaTools\Video\VideoInfoReaderInterface;

class GoogleVod2019Preset implements PresetInterface
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
    }

    public function getParams(int $width, int $height): VideoConvertParams
    {
        $params = (new VideoConvertParams())
            ->withVideoCodec('vp9');

        return $params;
    }
}
