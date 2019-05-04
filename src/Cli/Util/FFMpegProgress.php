<?php

declare(strict_types=1);

namespace Soluble\MediaTools\Cli\Util;

use Soluble\MediaTools\Common\Exception\InvalidArgumentException;
use Soluble\MediaTools\Video\SeekTime;

class FFMpegProgress
{
    public function __construct()
    {
    }

    public function getProgress(string $line): ?array
    {
        $line = str_replace(' ', '', $line);

        $result = preg_match('/frame=(?P<frame>\d+)(.*)size=(?P<size>\d+(KB|MB|B))(.*)time=(?P<time>([0-9\.:]+))(.*)speed=(?P<speed>([0-9\.]+))x/i', $line, $matches);

        if ($result === 1) {
            try {
                $time = SeekTime::convertHMSmToSeconds($matches['time']);
            } catch (InvalidArgumentException $e) {
                $time = 0;
            }

            return [
                'frame' => (int) $matches['frame'],
                'size'  => $matches['size'],
                'time'  => $time,
                'speed' => (float) $matches['speed'],
            ];
        }

        return null;
    }
}
