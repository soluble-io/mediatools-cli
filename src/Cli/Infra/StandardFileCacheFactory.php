<?php

declare(strict_types=1);

/**
 * @see       https://github.com/soluble-io/soluble-mediatools-cli for the canonical repository
 *
 * @copyright Copyright (c) 2018-2019 Sébastien Vanvelthem. (https://github.com/belgattitude)
 * @license   https://github.com/soluble-io/soluble-mediatools-cli/blob/master/LICENSE.md MIT
 */

namespace Soluble\MediaTools\Cli\Infra;

use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;

class StandardFileCacheFactory
{
    public function __invoke(ContainerInterface $container): CacheInterface
    {
        return new \Symfony\Component\Cache\Simple\FilesystemCache(
            'soluble-mediatools-cli',
            86400,
            \Soluble\MediaTools\Cli\Config\ConfigProvider::getProjectCacheDirectory()
        );
    }
}
