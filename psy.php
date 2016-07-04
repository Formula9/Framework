<?php

use F9\Application\Application;
use Illuminate\Contracts\Container\Container;
use Nine\Collections\Attributes;
use Nine\Collections\Config;
use Nine\Collections\GlobalScope;
use Nine\Collections\Paths;
use Nine\Database\Connections;
use Nine\Database\Database;
use Nine\Database\DB;
use Nine\Database\NineBase;
use Nine\Views\Blade;
use Nine\Views\BladeView;
use Nine\Views\BladeViewConfigurationInterface;
use Nine\Views\TwigView;
use Nine\Views\TwigViewConfigurationInterface;

include __DIR__ . '/tests/boot/boot.php';

//@formatter:off
$application    = forge()->make(Application::class);
$attributes     = forge()->make(Attributes::class);
$blade          = forge()->make(Blade::class);
$blade_config   = forge()->make(BladeViewConfigurationInterface::class);
$blade_view     = forge()->make(BladeView::class);
$config         = forge()->make(Config::class);
$connections    = forge()->make(Connections::class);
$database       = forge()->make(Database::class);
$db             = forge()->make(DB::class);
$global_scope   = forge()->make(GlobalScope::class);
$ninebase       = forge()->make(NineBase::class);
$paths          = forge()->make(Paths::class);
$twig_config    = forge()->make(TwigViewConfigurationInterface::class);
$twig_view      = forge()->make(TwigView::class);
$version        = app()::VERSION;

$db_connection  = app('db.connection');
$db_manager     = app('db');
$ioc            = forge()->make(Container::class);
