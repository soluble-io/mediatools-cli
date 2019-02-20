<?php

declare(strict_types=1);

namespace MediaToolsCliTest\Functional\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ScanCommandTest extends TestCase
{
    protected function setUp(): void
    {
    }

    public function testScanDirectories(): void
    {
        $app = new Application();
        //$app->add($this->commandRepo->getRegisteredCommand('pjbserver:stop'));
        //$command = $app->find('scan:videos');
        //$tester = new CommandTester($command);
        /*
                $tester->execute([
                    'config-file' => PjbServerTestConfig::getBaseDir() . '/config/pjbserver.config.php.dist'
                ]);
        
                self::assertEquals(0, $tester->getStatusCode());
        
        
                if (!file_exists($pid_file)) {
                    self::assertRegexp("/Server already stopped \(pid_file (.*) not found\)./", $tester->getDisplay());
                } else {
                    self::assertRegexp("/Server running on port $port successfully stopped/", $tester->getDisplay());
                }
        */
        self::assertTrue(true);
    }
}
