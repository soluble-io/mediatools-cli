<?php

declare(strict_types=1);

/**
 * @see       https://github.com/soluble-io/soluble-mediatools-cli for the canonical repository
 *
 * @copyright Copyright (c) 2018-2019 Sébastien Vanvelthem. (https://github.com/belgattitude)
 * @license   https://github.com/soluble-io/soluble-mediatools-cli/blob/master/LICENSE.md MIT
 */

namespace MediaToolsCliTest\FileSystem;

use PHPUnit\Framework\TestCase;
use Soluble\MediaTools\Cli\FileSystem\DirectoryScanner;

class DirectoryScannerTest extends TestCase
{
    public function setUp(): void
    {
    }

    public function testFindFiles(): void
    {
        $ds    = new DirectoryScanner();
        $files = $ds->findFiles(__DIR__, ['php']);
        self::assertGreaterThan(0, count($files));

        $files = $ds->findFiles(__DIR__);
        self::assertGreaterThan(0, count($files));


        $files = $ds->findFiles(__DIR__, ['PHP']);
        self::assertGreaterThan(0, count($files));

    }
}
