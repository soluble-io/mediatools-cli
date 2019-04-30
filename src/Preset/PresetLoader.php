<?php

declare(strict_types=1);

namespace Soluble\MediaTools\Preset;

class PresetLoader
{
    /**
     * @var PresetLocator
     */
    private $locator;

    public function __construct(PresetLocator $locator)
    {
        $this->locator = $locator;
    }

    /**
     * @param string $presetName
     *
     * @return PresetInterface
     */
    public function getPreset(string $presetName): PresetInterface
    {
        return $this->locator->getPreset($presetName);
    }
}
