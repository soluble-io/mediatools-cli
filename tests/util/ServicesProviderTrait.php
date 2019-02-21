<?php

declare(strict_types=1);

/**
 * @see       https://github.com/soluble-io/soluble-mediatools-cli for the canonical repository
 *
 * @copyright Copyright (c) 2018-2019 Sébastien Vanvelthem. (https://github.com/belgattitude)
 * @license   https://github.com/soluble-io/soluble-mediatools-cli/blob/master/LICENSE.md MIT
 */

namespace MediaToolsCliTest\Util;

use Psr\Container\ContainerInterface;
use Soluble\MediaTools\Cli\Config\ConfigProvider;
use Zend\ServiceManager\ServiceManager;

trait ServicesProviderTrait
{
    /**
     * @throws \Exception
     */
    public function getConfiguredContainer(bool $ensureBinariesExists = false): ContainerInterface
    {
        if (!defined('FFMPEG_BINARY_PATH')) {
            throw new \Exception('Missing phpunit constant FFMPEG_BINARY_PATH');
        }
        if (!defined('FFPROBE_BINARY_PATH')) {
            throw new \Exception('Missing phpunit constant FFPROBE_BINARY_PATH');
        }

        $ffmpegBinary  = FFMPEG_BINARY_PATH;
        $ffprobeBinary = FFPROBE_BINARY_PATH;
        if ($ensureBinariesExists) {
            if (mb_strpos(FFMPEG_BINARY_PATH, './') !== false) {
                // relative directory
                $ffmpegBinary = realpath(FFMPEG_BINARY_PATH);
                if ($ffmpegBinary === false || !file_exists($ffmpegBinary)) {
                    throw new \Exception(sprintf(
                        'FFMPEG binary does not exists: \'%s\', realpath returned: \'%s\'',
                        FFMPEG_BINARY_PATH,
                        is_bool($ffmpegBinary) ? 'false' : $ffmpegBinary
                    ));
                }
            }

            if (mb_strpos(FFPROBE_BINARY_PATH, './') !== false) {
                // relative directory
                $ffprobeBinary = realpath(FFPROBE_BINARY_PATH);
                if ($ffprobeBinary === false || !file_exists($ffprobeBinary)) {
                    throw new \Exception(sprintf(
                        'FFPROBE binary does not exists: \'%s\', realpath returned: \'%s\'',
                        FFPROBE_BINARY_PATH,
                        is_bool($ffprobeBinary) ? 'false' : $ffprobeBinary
                    ));
                }
            }
        }

        $config = [
            'soluble-mediatools' => [
                'ffmpeg.binary'  => $ffmpegBinary,
                'ffprobe.binary' => $ffprobeBinary,
            ],
        ];

        $sm = new ServiceManager(
            array_merge(
                [
                    'services' => [
                        'config' => $config,
                    ]],
                (new ConfigProvider())->getDependencies()
            )
        );

        return $sm;
    }
}
