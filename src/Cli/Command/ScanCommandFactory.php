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
use Soluble\MediaTools\Video\VideoInfoReaderInterface;

class ScanCommandFactory
{
    public function __invoke(ContainerInterface $container): ScanCommand
    {
        return new ScanCommand($container->get(VideoInfoReaderInterface::class));
    }
}
