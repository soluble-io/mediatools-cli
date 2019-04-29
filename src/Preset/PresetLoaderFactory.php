<?php

declare(strict_types=1);

/**
 * @see       https://github.com/soluble-io/soluble-mediatools-cli for the canonical repository
 *
 * @copyright Copyright (c) 2018-2019 Sébastien Vanvelthem. (https://github.com/belgattitude)
 * @license   https://github.com/soluble-io/soluble-mediatools-cli/blob/master/LICENSE.md MIT
 */

namespace Soluble\MediaTools\Preset;

use Psr\Container\ContainerInterface;

class PresetLoaderFactory
{
    public function __invoke(ContainerInterface $container): PresetLoader
    {
        return new PresetLoader(
            new PresetLocator(),
            $container
        );
    }
}
