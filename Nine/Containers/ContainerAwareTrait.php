<?php namespace Nine\Containers;

/**
 * @package Nine Containers
 * @version 0.4.3
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

trait ContainerAwareTrait
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     *
     * @return object
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Get the container.
     *
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

}
