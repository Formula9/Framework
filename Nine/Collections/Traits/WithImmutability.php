<?php namespace Nine\Traits;

use Nine\Collections\Exceptions\ImmutableViolationException;

/**
 * This trait exposes immutability violation exceptions for common
 * methods.
 *
 * @see Nine\Attributes
 *
 * @package Nine Traits
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

trait WithImmutability
{
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
     * @param mixed $offset
     * @param mixed $value
     *
     * @throws ImmutableViolationException
     */
    public function offsetSet($offset, $value)
    {
        throw new ImmutableViolationException();
    }

    /**
     * @param mixed|string $offset
     *
     * @throws ImmutableViolationException
     */
    public function offsetUnset($offset)
    {
        throw new ImmutableViolationException('Cannot remove an immutable item.');
    }

}
