<?php

declare(strict_types=1);

namespace Soluble\MediaTools\Cli;

use Psr\Container\ContainerInterface;
use Soluble\MediaTools\Video\VideoAnalyzerInterface;
use Soluble\MediaTools\Video\VideoConverterInterface;
use Soluble\MediaTools\Video\VideoInfoReaderInterface;

class ConvertCommandFactory
{
    public function __invoke(ContainerInterface $container): ConvertCommand
    {
        return new ConvertCommand(
            $container->get(VideoInfoReaderInterface::class),
            $container->get(VideoAnalyzerInterface::class),
            $container->get(VideoConverterInterface::class)
        );
    }
}
