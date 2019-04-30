<?php

declare(strict_types=1);

/**
 * @see       https://github.com/soluble-io/soluble-mediatools-cli for the canonical repository
 *
 * @copyright Copyright (c) 2018-2019 Sébastien Vanvelthem. (https://github.com/belgattitude)
 * @license   https://github.com/soluble-io/soluble-mediatools-cli/blob/master/LICENSE.md MIT
 */

namespace MediaToolsCliTest\Functional\Command;

use InvalidArgumentException;
use MediaToolsCliTest\Util\TestConfigProviderTrait;
use PHPUnit\Framework\TestCase;
use Soluble\MediaTools\Cli\Command\ConvertDirCommandFactory;
use Soluble\MediaTools\Preset\PresetInterface;
use Soluble\MediaTools\Preset\PresetLocator;
use Soluble\MediaTools\Video\SeekTime;
use Soluble\MediaTools\Video\VideoConvertParams;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ConvertDirCommandTest extends TestCase
{
    use TestConfigProviderTrait;

    /** @var Application */
    private $app;

    /** @var Command */
    private $command;

    protected function setUp(): void
    {
        $this->app = new Application();
        $container = $this->getConfiguredContainer();
        $container->get(PresetLocator::class)->addPreset();
        $this->app->add((new ConvertDirCommandFactory())($container));
        $this->command = $this->app->find('convert:directory');
    }

    public function testScanDirectories(): void
    {
        $tester = new CommandTester($this->command);

        $tester->execute([
            '--dir'    => $this->getAssetsTestDirectory(),
            '--preset' => 'test_preset',
        ]);

        self::assertEquals(0, $tester->getStatusCode());
    }

    public function testScanDirectoriesThrowsInvalidDirectory(): void
    {
        self::expectException(InvalidArgumentException::class);
        $tester = new CommandTester($this->command);
        $tester->execute([
            '--dir' => './unexistent/directory',
        ]);
    }

    public function testScanDirectoriesThrowsInvalidDirectory2(): void
    {
        self::expectException(InvalidArgumentException::class);
        $tester = new CommandTester($this->command);
        $tester->execute([]);
    }

    protected function getTestPreset(): PresetInterface
    {
        return new class() implements PresetInterface {
            public function getName(): string
            {
                return 'test_preset';
            }

            public function getParams(string $file, ?int $width = null, ?int $height = null): VideoConvertParams
            {
                return (new VideoConvertParams())
                    ->withVideoCodec('h264')
                    ->withSeekStart(new SeekTime(0))
                    ->withSeekEnd(new SeekTime(0.3));
            }
        };
    }
}
