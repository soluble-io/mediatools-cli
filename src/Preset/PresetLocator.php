<?php

declare(strict_types=1);

namespace Soluble\MediaTools\Preset;

use Soluble\MediaTools\Preset\MP4\StreamableH264Preset;
use Soluble\MediaTools\Preset\Prod\ResolvePreset;
use Soluble\MediaTools\Preset\WebM\GoogleVod2019Preset;

class PresetLocator
{
    public const BUILTIN_PRESETS = [
        ResolvePreset::class,
        StreamableH264Preset::class,
        GoogleVod2019Preset::class
    ];

    /**
     * @var string[]
     */
    private $paths = [];

    /**
     * @var string[]
     */
    private $presets;

    public function __construct(array $paths = [])
    {
        $this->paths   = array_merge($paths, [__DIR__]);
        $this->presets = self::BUILTIN_PRESETS;
    }

    public function isSupported(string $name): bool
    {
        return in_array($name, $this->presets, true);
    }
}
