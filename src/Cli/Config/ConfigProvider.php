<?php

declare(strict_types=1);

namespace Soluble\MediaTools\Cli\Config;

use Soluble\MediaTools\Cli\Command\ConvertCommand;
use Soluble\MediaTools\Cli\Command\ConvertCommandFactory;
use Soluble\MediaTools\Cli\Command\ScanCommand;
use Soluble\MediaTools\Cli\Command\ScanCommandFactory;
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
                    ConvertCommand::class => ConvertCommandFactory::class,
                    ScanCommand::class    => ScanCommandFactory::class
            ],
        ]
        );
    }
}
