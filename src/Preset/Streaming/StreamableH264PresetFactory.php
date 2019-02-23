<?php

declare(strict_types=1);

/**
 * @see       https://github.com/soluble-io/soluble-mediatools-cli for the canonical repository
 *
 * @copyright Copyright (c) 2018-2019 Sébastien Vanvelthem. (https://github.com/belgattitude)
 * @license   https://github.com/soluble-io/soluble-mediatools-cli/blob/master/LICENSE.md MIT
 */

namespace Soluble\MediaTools\Preset\MP4;

use Psr\Container\ContainerInterface;
use Soluble\MediaTools\Cli\Service\MediaToolsServiceInterface;

class StreamableH264PresetFactory
{
    public function __invoke(ContainerInterface $container): StreamableH264Preset
    {
        return new StreamableH264Preset(
            $container->get(MediaToolsServiceInterface::class)
        );
    }
}
