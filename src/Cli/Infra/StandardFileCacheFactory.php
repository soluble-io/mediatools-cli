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
use Soluble\MediaTools\Cli\Exception\ConfigException;
use Soluble\MediaTools\Common\Cache\NullCache;

class StandardFileCacheFactory
{
    /**
     * @throws ConfigException
     */
    public function __invoke(ContainerInterface $container): CacheInterface
    {
        $cache =  $container->get('config')['soluble-mediatools']['cache'] ?? null;
        if ($cache === null) {
            return new NullCache();
        } elseif (!is_callable($cache)) {
            throw new ConfigException("Config exception, 'cache' must be callable or null");
        }

        $c = $cache();
        if (!$c instanceof CacheInterface) {
            throw new ConfigException("Config exception, 'cache' must return a psr16 cache implementation");
        }

        return $c;
    }
}
