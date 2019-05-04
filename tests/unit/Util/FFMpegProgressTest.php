<?php

declare(strict_types=1);

/**
 * @see       https://github.com/soluble-io/soluble-mediatools-cli for the canonical repository
 *
 * @copyright Copyright (c) 2018-2019 Sébastien Vanvelthem. (https://github.com/belgattitude)
 * @license   https://github.com/soluble-io/soluble-mediatools-cli/blob/master/LICENSE.md MIT
 */

namespace MediaToolsCliTest\Util;

use PHPUnit\Framework\TestCase;
use Soluble\MediaTools\Cli\Util\FFMpegProgress;

class FFMpegProgressTest extends TestCase
{
    public function setUp(): void
    {
    }

    public function testGetProgress(): void
    {
        $p    = new FFMpegProgress();
        $line = 'frame=  991 fps= 46 q=1.0 size=  588032kB time=00:00:33.04 bitrate=145774.2kbits/s speed=1.53x';

        $info = $p->getProgress($line);

        self::assertSame([
            'frame' => 991,
            'size'  => '588032kB',
            'time'  => 33.04,
            'speed' => 1.53
        ], $info);
    }
}
