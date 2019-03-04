<?php

declare(strict_types=1);

/**
 * @see       https://github.com/soluble-io/soluble-mediatools-cli for the canonical repository
 *
 * @copyright Copyright (c) 2018-2019 Sébastien Vanvelthem. (https://github.com/belgattitude)
 * @license   https://github.com/soluble-io/soluble-mediatools-cli/blob/master/LICENSE.md MIT
 */

namespace Soluble\MediaTools\Cli\Infra;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Soluble\MediaTools\Cli\Config\ConfigProvider;

class StandardFileLoggerFactory
{
    public const STANDARD_LOG_FILE = 'mediatools.log';
    public const ERROR_LOG_FILE    = 'mediatools-error.log';

    public function __invoke(ContainerInterface $container): LoggerInterface
    {
        $logger = new Logger('soluble-mediatools');
        $logger->pushHandler(new StreamHandler(
            self::getDefaultLogDirectory() . DIRECTORY_SEPARATOR . self::STANDARD_LOG_FILE,
            Logger::INFO,
            true,
            0660
        ));

        $logger->pushHandler(new StreamHandler(
            self::getDefaultLogDirectory() . DIRECTORY_SEPARATOR . self::ERROR_LOG_FILE,
            Logger::ERROR,
            true,
            0660
        ));

        $logger->pushProcessor(new PsrLogMessageProcessor());

        return $logger;
    }

    public static function getDefaultLogDirectory(): string
    {
        return ConfigProvider::getProjectLogDirectory();
    }
}
