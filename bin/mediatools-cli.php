<?php

$basePath = dirname(__DIR__, 1);

require $basePath . '/vendor/autoload.php';

use Symfony\Component\Console\Application;
use Zend\ServiceManager\ServiceManager;
use Soluble\MediaTools\Cli\Config\ConfigProvider;

$config = require(implode(DIRECTORY_SEPARATOR, [$basePath, 'config', 'soluble-mediatools-cli.config.php']));

// Service manager
$container = new ServiceManager(
    array_merge([
        // In Zend\ServiceManager configuration will be set
        // in 'services'.'config'.
        'services' => [
            'config' => $config
        ]],
        // Here the factories
        (new ConfigProvider())->getDependencies()
    ));

$application = new Application('Mediatools console');

$commands = $container->get('config')['console']['commands'];
foreach ($commands as $command) {
    $application->add($container->get($command));
}

$application->run();
