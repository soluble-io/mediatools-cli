<?php

declare(strict_types=1);

namespace Soluble\MediaTools\Preset\Prod;

use Soluble\MediaTools\Cli\Service\MediaToolsServiceInterface;
use Soluble\MediaTools\Preset\PresetInterface;
use Soluble\MediaTools\Video\Filter\Type\FFMpegVideoFilterInterface;
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

        // ffmpeg -i <input> -c:v dnxhd -vf "scale=1920:1080,fps=30000/1001,format=yuv422p" -b:v 145M -c:a pcm_s16le test3.mov
        // http://www.deb-indus.org/tuto/ffmpeg-howto.htm#Encoding_VC-3
        $params = (new VideoConvertParams())
            ->withVideoCodec('dnxhd')
            ->withAudioCodec('pcm_s16le')
            ->withVideoBitrate('145M')
            ->withVideoFilter(new class() implements FFMpegVideoFilterInterface {
                public function getFFmpegCLIValue(): string
                {
                    return 'scale=1920:1080,fps=30000/1001,format=yuv422p';
                }
            })
            ->withOutputFormat('mov');

        return $params;
    }

    public function getFileExtension(): string
    {
        return 'mov';
    }
}

/*
 * Project Format	Resolution	Frame Size	Bits	FPS	<bitrate>
1080i / 59.94	DNxHD 220	1920 x 1080	8	29.97	220Mb
1080i / 59.94	DNxHD 145	1920 x 1080	8	29.97	145Mb
1080i / 50	DNxHD 185	1920 x 1080	8	25	185Mb
1080i / 50	DNxHD 120	1920 x 1080	8	25	120Mb
1080p / 25	DNxHD 185	1920 x 1080	8	25	185Mb
1080p / 25	DNxHD 120	1920 x 1080	8	25	120Mb
1080p / 25	DNxHD 36	1920 x 1080	8	25	36Mb
1080p / 24	DNxHD 175	1920 x 1080	8	24	175Mb
1080p / 24	DNxHD 115	1920 x 1080	8	24	115Mb
1080p / 24	DNxHD 36	1920 x 1080	8	24	36Mb
1080p / 23.976	DNxHD 175	1920 x 1080	8	23.976	175Mb
1080p / 23.976	DNxHD 115	1920 x 1080	8	23.976	115Mb
1080p / 23.976	DNxHD 36	1920 x 1080	8	23.976	36Mb
1080p / 29.7	DNxHD 45	1920 x 1080	8	29.97	45Mb
720p / 59.94	DNxHD 220	1280x720	8	59.94	220Mb
720p / 59.94	DNxHD 145	1280x720	8	59.94	145Mb
720p / 50	DNxHD 175	1280x720	8	50	175Mb
720p / 50	DNxHD 115	1280x720	8	50	115Mb
720p / 23.976	DNxHD 90	1280x720	8	23.976	90Mb
720p / 23.976	DNxHD 60	1280x720	8	23.976	60Mb
 */
