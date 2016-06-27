<?php namespace F9\Exceptions;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

class FeatureNotImplemented extends \Exception
{
    public function __construct($message = null)
    {
        parent::__construct($message ?: 'This feature has not yet been implemented.');
    }
}
