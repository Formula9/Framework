<?php namespace Nine\Containers;

/**
 * @package Nine Containers
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

interface ContainerAwareInterface
{
    /**
     * @param $container
     */
    public function setContainer(Container $container);

    /**
     * @return $container
     */
    public function getContainer();
}
