<?php

declare(strict_types=1);

namespace Soluble\MediaTools\Preset;

use Psr\Container\ContainerInterface;
use Soluble\MediaTools\Cli\Exception\UnknownPresetException;
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
     * @var array<string, PresetInterface|string>
     */
    private $presets = [];

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container, array $paths = [])
    {
        $this->paths     = array_merge($paths, [__DIR__]);
        $this->container = $container;
        $this->loadBuiltInPresets();
    }

    protected function loadBuiltInPresets(): void
    {
        foreach (self::BUILTIN_PRESETS as $preset) {
            $this->presets[$preset] = $preset;
        }
    }

    public function addPreset(string $name, PresetInterface $preset): void
    {
        $this->presets[$name] = $preset;
    }

    public function getPreset(string $name): PresetInterface
    {
        if (!$this->hasPreset($name)) {
            throw new UnknownPresetException(sprintf('Preset %s does not exists', $name));
        }
        $preset = $this->presets[$name];
        if ($preset instanceof PresetInterface) {
            return $preset;
        }

        return $this->container->get($name);
    }

    public function hasPreset(string $name): bool
    {
        return in_array($name, $this->presets, true);
    }
}
