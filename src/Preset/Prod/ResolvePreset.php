<?php

declare(strict_types=1);

namespace Soluble\MediaTools\Preset\Prod;

use Soluble\MediaTools\Cli\Service\MediaToolsServiceInterface;
use Soluble\MediaTools\Preset\PresetInterface;
use Soluble\MediaTools\Video\Filter\ScaleFilter;
use Soluble\MediaTools\Video\Filter\VideoFilterChain;
use Soluble\MediaTools\Video\VideoConvertParams;
use Soluble\MediaTools\Video\VideoInfoInterface;

class ResolvePreset implements PresetInterface
{
    /** @var MediaToolsServiceInterface */
    private $mediaTools;

    /** @var VideoInfoInterface */
    private $videoInfo;

    public function __construct(MediaToolsServiceInterface $mediaTools)
    {
        $this->mediaTools = $mediaTools;
    }

    private function getVideoInfo(string $file): VideoInfoInterface
    {
        if ($this->videoInfo === null) {
            $this->videoInfo = $this->mediaTools->getReader()->getInfo($file);
        }

        return $this->videoInfo;
    }

    public function getParams(string $file, ?int $width = null, ?int $height = null): VideoConvertParams
    {
        $info = $this->getVideoInfo($file);

        $firstAudio = $info->getAudioStreams()->getFirst();
        $firstVideo = $info->getVideoStreams()->getFirst();

        $filters = new VideoFilterChain();

        if (($width !== null && $firstVideo->getWidth() !== $width) ||
            ($height !== null && $firstVideo->getHeight() !== $height)) {
            $filters->addFilter(new ScaleFilter($width, $height));
        }

        $params = (new VideoConvertParams())
            ->withVideoCodec('libx264')
            ->withStreamable(true)
            ->withVideoFilter($filters)
            ->withOutputFormat('mp4');

        if (mb_strtolower($firstAudio->getCodecName()) !== 'aac') {
            $params = $params->withAudioCodec('aac');
        }

        return $params;
    }
}
