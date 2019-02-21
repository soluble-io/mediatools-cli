<?php

declare(strict_types=1);

/**
 * @see       https://github.com/soluble-io/soluble-mediatools-cli for the canonical repository
 *
 * @copyright Copyright (c) 2018-2019 Sébastien Vanvelthem. (https://github.com/belgattitude)
 * @license   https://github.com/soluble-io/soluble-mediatools-cli/blob/master/LICENSE.md MIT
 */

namespace MediaToolsCliTest\Functional\Command;

use MediaToolsCliTest\Util\TestConfigProviderTrait;
use PHPUnit\Framework\TestCase;
use Soluble\MediaTools\Cli\Command\ScanCommandFactory;
use Soluble\MediaTools\Cli\Exception\MissingFFProbeBinaryException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ScanCommandTest extends TestCase
{
    use TestConfigProviderTrait;

    /** @var Application */
    private $app;

    /** @var Command */
    private $command;

    protected function setUp(): void
    {
        $this->app = new Application();
        $this->app->add((new ScanCommandFactory())($this->getConfiguredContainer()));
        $this->command = $this->app->find('scan:videos');
    }

    public function testScanDirectories(): void
    {
        $tester = new CommandTester($this->command);

        $tester->execute([
            '--dir' => sys_get_temp_dir(),
        ]);
        self::assertEquals(0, $tester->getStatusCode());

        $tester->execute([
            '--dir' => $this->getAssetsTestDirectory(),
        ]);

        $output = $tester->getDisplay();
        self::assertRegExp('/big_buck_bunny_low.m4v/', $output);
        self::assertRegExp('/yuv420p/', $output);

        self::assertEquals(0, $tester->getStatusCode());
    }

    public function testMissingFFProbeBinary(): void
    {
        self::expectException(MissingFFProbeBinaryException::class);
        $app     = new Application();
        $command = (new ScanCommandFactory())($this->getConfiguredContainer(false, './path/ffmpeg', './path/ffprobe'));
        $app->add($command);

        $command = $app->find('scan:videos');

        $tester = new CommandTester($command);
        $tester->execute([
            '--dir' => $this->getAssetsTestDirectory(),
        ]);
    }

    public function testScanDirectoriesThrowsInvalidDirectory(): void
    {
        self::expectException(\InvalidArgumentException::class);
        $tester = new CommandTester($this->command);
        $tester->execute([
            '--dir' => './unexistent/directory',
        ]);
    }
}
