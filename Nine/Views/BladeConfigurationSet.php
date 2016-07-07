<?php namespace Nine\Views;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

use Illuminate\Events\Dispatcher;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Nine\Collections\Scope;
use Nine\Exceptions\ImmutableViolationException;

/**
 * **BladeConfigurationSet is an immutable data object passed to a Blade|BladeView constructor.**
 *
 * The blade context is populated with the required contextual references and passed to
 * a Blade constructor. In the F9 framework, this is accomplished by the BladeViewServiceProvider.
 */
final class BladeConfigurationSet extends Scope implements BladeViewConfigurationInterface
{
    /**
     * BladeScope constructor.
     *
     * @param array $context_settings -
     */
    public function __construct($context_settings)
    {
        parent::__construct($context_settings);
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @throws ImmutableViolationException
     */
    public function __set($key, $value)
    {
        throw new ImmutableViolationException();
    }

    /**
     * @return CompilerEngine
     */
    public function engine() : CompilerEngine
    {
        return $this['engine'];
    }

    /**
     * @return Dispatcher
     */
    public function events()
    {
        return $this['events'];
    }

    /**
     * @return Factory
     */
    public function factory() : Factory
    {
        return $this['factory'];
    }

    /**
     * @return FileViewFinder
     */
    public function finder() : FileViewFinder
    {
        return $this['finder'];
    }

    /**
     * @return Scope
     */
    public function globalScope() : Scope
    {
        return $this['global'];
    }

    /**
     * @return array
     */
    public function paths() : array
    {
        return $this['settings.template_paths'];
    }

    /**
     * @return array
     */
    public function settings() : array
    {
        return $this['settings'];
    }
}
