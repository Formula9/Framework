<?php namespace Nine\Collections\Exceptions;

/**
 * Thrown when an attempt is made to alter an immutable object/property.
 *
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */
class ImmutableViolationException extends \Exception
{
    /**
     * ImmutableViolationException constructor.
     *
     * @param string $message
     */
    public function __construct($message = 'Setting magic or other properties is not allowed in an immutable data object.')
    {
        parent::__construct($message);
    }
}
