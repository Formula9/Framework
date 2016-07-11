<?php

use Auryn\CachingReflector;
use Auryn\Injector;
use F9\Container\Potion;

include __DIR__ . '/tests/boot/boot.php';

$potion = new Potion(new Injector(new CachingReflector()));

//AppFactory::make($paths);
//
////@formatter:off
//$application    = forge()->make(Application::class);
//$attributes     = forge()->make(Attributes::class);
//$blade          = forge()->make(Blade::class);
//$blade_config   = forge()->make(BladeViewConfigurationInterface::class);
//$blade_view     = forge()->make(BladeView::class);
//$config         = forge()->make(Config::class);
//$connections    = forge()->make(Connections::class);
//$database       = forge()->make(Database::class);
//$db             = forge()->make(DB::class);
//$global_scope   = forge()->make(GlobalScope::class);
//$ninebase       = forge()->make(NineBase::class);
//$paths          = forge()->make(Paths::class);
//$twig_config    = forge()->make(TwigViewConfigurationInterface::class);
//$twig_view      = forge()->make(TwigView::class);
//$version        = app()::VERSION;
//
//$db_connection  = app('db.connection');
//$db_manager     = app('db');
//$ioc            = forge()->make(Container::class);
//
//$migrate_console = new Illuminate\Console\Application(forge('illuminate.container'), forge('illuminate.events'), '0.1');
//(new MigrationServiceProvider($application))->register($application);
////@formatter:on
//
//$commands = glob('Nine/Database/Console/Migrations/*.php');


