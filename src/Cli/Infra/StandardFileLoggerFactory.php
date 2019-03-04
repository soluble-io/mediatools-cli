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
use Psr\Log\LoggerInterface;

class StandardFileLoggerFactory
{
    public function __invoke(ContainerInterface $container): LoggerInterface
    {
        $logger = new \Monolog\Logger('soluble-mediatools-cli');
        $logger->pushHandler(
            new \Monolog\Handler\StreamHandler(
                \Soluble\MediaTools\Cli\Config\ConfigProvider::getProjectLogDirectory() . DIRECTORY_SEPARATOR . 'mediatools.log',
                \Monolog\Logger::INFO
            )
        );

        return $logger;
    }
}
