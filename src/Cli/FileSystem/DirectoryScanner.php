<?php

declare(strict_types=1);

namespace Soluble\MediaTools\Cli\FileSystem;

use Symfony\Component\Finder\Finder;

class DirectoryScanner
{
    /**
     * @return array<\SplFileInfo>
     */
    public function findFiles(string $path, ?array $validExtensions = null): array
    {
        $files = (new Finder())->files()
            ->in($path)
            ->ignoreUnreadableDirs();

        if ($validExtensions !== null && count($validExtensions) > 0) {
            $files->name(sprintf(
                '/\.(%s)$/i',
                implode('|', $validExtensions)
            ));
        }

        $videos = [];

        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            $videos[] = $file;
        }

        return $videos;
    }
}
