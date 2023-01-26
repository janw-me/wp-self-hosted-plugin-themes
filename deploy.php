#!/usr/bin/env php
<?php

use JanwMe\WpSelfHostedPluginThemes\Command;
use Symfony\Component\Console\Application;

require __DIR__ . '/vendor/autoload.php';

$application = new Application( 'Deploy', '1.0.0' );
$command     = new Command();
$application->add( $command );
$application->setDefaultCommand( $command->getName(), true );
$application->run();
