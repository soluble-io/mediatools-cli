<?php

declare(strict_types=1);

namespace Soluble\MediaTools\Preset;

class PresetLoader
{
    /** @var PresetLocator */
    private $locator;

    public function __construct(PresetLocator $locator)
    {
        $this->locator = $locator;
    }

    public function getPreset(string $presetName): PresetInterface
    {
        return $this->locator->getPreset($presetName);
    }

    public function getLocator(): PresetLocator
    {
        return $this->locator;
    }
}
