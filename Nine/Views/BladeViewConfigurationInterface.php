<?php

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */
namespace Nine\Views;

use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */
interface BladeViewConfigurationInterface extends ViewConfigurationInterface
{
    /**
     * @return CompilerEngine
     */
    public function engine() : CompilerEngine;

    /**
     * @return Factory
     */
    public function factory() : Factory;

    /**
     * @return FileViewFinder
     */
    public function finder() : FileViewFinder;

}
