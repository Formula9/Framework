<?php namespace F9\Support\Contracts;

use Nine\Views\View;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

interface ComposerInterface
{
    /**
     * @param View $view
     *
     * @return mixed
     */
    public function compose($view);
}
