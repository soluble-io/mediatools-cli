<?php

declare(strict_types=1);

namespace Soluble\MediaTools\Cli\FileSystem;

use Symfony\Component\Finder\Finder;

class DirectoryScanner
{
    public function getFinder(string $path, ?array $validExtensions = null): Finder
    {
        $finder = (new Finder())->files()
            ->in($path)
            ->ignoreUnreadableDirs();
        if ($validExtensions !== null && count($validExtensions) > 0) {
            $finder->name(sprintf(
                '/\.(%s)$/i',
                implode('|', $validExtensions)
            ));
        }

        return $finder;
    }

    /**
     * @psalm-suppress PossiblyInvalidArgument
     *
     * @return array<\SplFileInfo>
     */
    public function findFiles(string $path, ?array $validExtensions = null, bool $recursive = true): array
    {
        $finder = $this->getFinder($path, $validExtensions);

        if (!$recursive) {
            $finder->depth('== 0');
        }

        return iterator_to_array($finder->getIterator());
    }
}
