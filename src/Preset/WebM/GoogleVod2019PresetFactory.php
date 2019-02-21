<?php

declare(strict_types=1);

/**
 * @see       https://github.com/soluble-io/soluble-mediatools-cli for the canonical repository
 *
 * @copyright Copyright (c) 2018-2019 Sébastien Vanvelthem. (https://github.com/belgattitude)
 * @license   https://github.com/soluble-io/soluble-mediatools-cli/blob/master/LICENSE.md MIT
 */

namespace Soluble\MediaTools\Preset\WebM;

use Psr\Container\ContainerInterface;
use Soluble\MediaTools\Video\VideoAnalyzerInterface;
use Soluble\MediaTools\Video\VideoConverterInterface;
use Soluble\MediaTools\Video\VideoInfoReaderInterface;

class GoogleVod2019PresetFactory
{
    public function __invoke(ContainerInterface $container): GoogleVod2019Preset
    {
        return new GoogleVod2019Preset(
            $container->get(VideoInfoReaderInterface::class),
            $container->get(VideoConverterInterface::class),
            $container->get(VideoAnalyzerInterface::class)
        );
    }
}
