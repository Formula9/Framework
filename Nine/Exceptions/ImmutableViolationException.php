<?php namespace Nine\Exceptions;

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
