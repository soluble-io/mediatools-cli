<?php

declare(strict_types=1);

namespace Soluble\MediaTools\Cli\Media;

class FileExtensions
{
    public const BUILTIN_EXTENSIONS = [
        'mov',
        'mp4',
        'm4v',
        'avi',
        'mkv',
        'flv',
        'webm',
    ];

    /**
     * @return string[]
     */
    public function getMediaExtensions(): array
    {
        return self::BUILTIN_EXTENSIONS;
    }
}
