<?php

use Nette\Diagnostics\Debugger,
	Nette\Application\Routers\SimpleRouter,
	Nette\Application\Routers\Route;

// Load Nette Framework
require LIBS_DIR . '/Nette/loader.php';

// Enable Nette Debugger for error visualisation & logging
Debugger::$logDirectory = __DIR__ . '/../log';
Debugger::$strictMode = TRUE;
Debugger::enable(Debugger::DEVELOPMENT);

// Configure application
$configurator = new Nette\Config\Configurator;
$configurator->setProductionMode(FALSE);
$configurator->setTempDirectory(__DIR__ . '/../temp');

// Enable RobotLoader - this will load all classes automatically
$configurator->createRobotLoader()
	->addDirectory(APP_DIR)
	->addDirectory(LIBS_DIR)
	->register();

// Create Dependency Injection container from config.neon file
$configurator->addConfig(__DIR__ . '/config.neon');

$configurator->onCompile[] = function ($configurator, $compiler) {
    $compiler->addExtension('dibi', new DibiNetteExtension);
};

$container = $configurator->createContainer();

$container->router[] = new SimpleRouter('Changelog:default');


// expirace prihlaseni
$container->user->setExpiration('+ 7 days', FALSE);

// Run the application!
$application = $container->application;
$application->run();
