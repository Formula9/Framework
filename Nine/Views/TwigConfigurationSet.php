<?php namespace Nine\Views;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

use Nine\Collections\Scope;
use Nine\Events\Events;
use Nine\Exceptions\ImmutableViolationException;

/**
 * **TwigConfigurationSet is an immutable data object passed to a TwigView constructor.**
 *
 * The twig context is populated with the required contextual references and passed to
 * a TwigView constructor. In the F9 framework, this is accomplished by the TwigViewServiceProvider.
 */
final class TwigConfigurationSet extends Scope implements TwigViewConfigurationInterface
{
    /**
     * BladeScope constructor.
     *
     * @param array $twig_settings
     */
    public function __construct($twig_settings)
    {
        parent::__construct($twig_settings);
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
     * @return Events
     */
    public function events()
    {
        return $this['events'];
    }

    /**
     * @return callable
     */
    public function finder() : callable
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
        return $this['settings'];
    }

    /**
     * @return array
     */
    public function settings() : array
    {
        return $this['settings'];
    }
}
