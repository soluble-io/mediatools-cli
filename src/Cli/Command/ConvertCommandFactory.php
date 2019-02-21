<?php

declare(strict_types=1);

/**
 * @see       https://github.com/soluble-io/soluble-mediatools-cli for the canonical repository
 *
 * @copyright Copyright (c) 2018-2019 Sébastien Vanvelthem. (https://github.com/belgattitude)
 * @license   https://github.com/soluble-io/soluble-mediatools-cli/blob/master/LICENSE.md MIT
 */

namespace Soluble\MediaTools\Cli\Command;

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
