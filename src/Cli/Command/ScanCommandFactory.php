<?php

declare(strict_types=1);

namespace Soluble\MediaTools\Cli\Command;

use Psr\Container\ContainerInterface;
use Soluble\MediaTools\Video\VideoInfoReaderInterface;

class ScanCommandFactory
{
    public function __invoke(ContainerInterface $container): ScanCommand
    {
        return new ScanCommand($container->get(VideoInfoReaderInterface::class));
    }
}
