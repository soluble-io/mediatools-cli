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

    public function getName(): string
    {
        return __CLASS__;
    }

    public function getParams(string $file, ?int $width = null, ?int $height = null): VideoConvertParams
    {
        $params = (new VideoConvertParams())
            ->withVideoCodec('vp9');

        return $params;
    }
}
