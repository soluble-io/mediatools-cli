<?php

declare(strict_types=1);

/**
 * @see       https://github.com/soluble-io/soluble-mediatools-cli for the canonical repository
 *
 * @copyright Copyright (c) 2018-2019 Sébastien Vanvelthem. (https://github.com/belgattitude)
 * @license   https://github.com/soluble-io/soluble-mediatools-cli/blob/master/LICENSE.md MIT
 */

namespace Soluble\MediaTools\Cli\Config;

use Soluble\MediaTools\Cli\Command\ConvertDirCommand;
use Soluble\MediaTools\Cli\Command\ConvertDirCommandFactory;
use Soluble\MediaTools\Cli\Command\ScanCommand;
use Soluble\MediaTools\Cli\Command\ScanCommandFactory;
use Soluble\MediaTools\Cli\Service\MediaToolsServiceFactory;
use Soluble\MediaTools\Cli\Service\MediaToolsServiceInterface;
use Soluble\MediaTools\Preset\MP4\StreamableH264Preset;
use Soluble\MediaTools\Preset\MP4\StreamableH264PresetFactory;
use Soluble\MediaTools\Preset\WebM\GoogleVod2019Preset;
use Soluble\MediaTools\Preset\WebM\GoogleVod2019PresetFactory;
use Soluble\MediaTools\Video\Config\ConfigProvider as VideoConfigProvider;

class ConfigProvider
{
    /**
     * Returns the configuration array.
     *
     * To add a bit of a structure, each section is defined in a separate
     * method which returns an array with its configuration.
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    /**
     * Returns the container dependencies.
     *
     * @return array<string, array>
     */
    public function getDependencies(): array
    {
        return array_merge_recursive(
            (new VideoConfigProvider())->getDependencies(),
            [
                'factories'  => [
                    // Services
                    MediaToolsServiceInterface::class => MediaToolsServiceFactory::class,

                    // Commands
                    ConvertDirCommand::class => ConvertDirCommandFactory::class,
                    ScanCommand::class       => ScanCommandFactory::class,

                    // Presets
                    GoogleVod2019Preset::class  => GoogleVod2019PresetFactory::class,
                    StreamableH264Preset::class => StreamableH264PresetFactory::class,
                ],
            ]
        );
    }

    /**
     * Return build console commands class names.
     *
     * @return string[]
     */
    public function getConsoleCommands(): array
    {
        return [
            \Soluble\MediaTools\Cli\Command\ScanCommand::class,
            \Soluble\MediaTools\Cli\Command\ConvertDirCommand::class,
        ];
    }

    /**
     * @throws \RuntimeException
     */
    public static function getProjectBaseDirectory(): string
    {
        $baseDir = dirname(__FILE__, 3);
        if (!is_dir($baseDir)) {
            throw new \RuntimeException(sprintf('Cannot get project base directory %s', $baseDir));
        }

        return $baseDir;
    }

    /**
     * @throws \RuntimeException
     */
    public static function getProjectCacheDirectory(): string
    {
        $cacheDir = self::getProjectBaseDirectory() . implode(DIRECTORY_SEPARATOR, ['data', 'cache']);

        if (!is_dir($cacheDir)) {
            throw new \RuntimeException(sprintf('Cache directory %s does not exists or invalid.', $cacheDir));
        }

        if (!is_readable($cacheDir) || !is_writable($cacheDir)) {
            throw new \RuntimeException(sprintf('Cache directory %s must have read/write access.', $cacheDir));
        }

        return $cacheDir;
    }

    /**
     * @throws \RuntimeException
     */
    public static function getProjectLogDirectory(): string
    {
        $logDir = self::getProjectBaseDirectory() . implode(DIRECTORY_SEPARATOR, ['data', 'logs']);

        if (!is_dir($logDir)) {
            throw new \RuntimeException(sprintf('Log directory %s does not exists or invalid.', $logDir));
        }

        if (!is_readable($logDir) || !is_writable($logDir)) {
            throw new \RuntimeException(sprintf('Cache directory %s must have read/write access.', $logDir));
        }

        return $logDir;
    }
}
