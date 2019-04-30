<?php

declare(strict_types=1);

namespace Soluble\MediaTools\Preset;

use Soluble\MediaTools\Video\VideoConvertParams;

interface PresetInterface
{
    public function getParams(string $file, ?int $width = null, ?int $height = null): VideoConvertParams;

    public function getFileExtension(): string;
}
