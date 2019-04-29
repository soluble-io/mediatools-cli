<?php

declare(strict_types=1);

namespace Soluble\MediaTools\Preset;

use Psr\Container\ContainerInterface;
use Soluble\MediaTools\Cli\Exception\UnknownPresetException;

class PresetLoader
{
    /**
     * @var PresetLocator
     */
    private $locator;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(PresetLocator $locator, ContainerInterface $container)
    {
        $this->locator   = $locator;
        $this->container = $container;
    }

    public function getPreset(string $presetName): PresetInterface
    {
        if (!$this->locator->isSupported($presetName)) {
            throw new UnknownPresetException(sprintf('Preset %s does not exists', $presetName));
        }

        return $this->container->get($presetName);
    }
}
