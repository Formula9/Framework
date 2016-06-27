<?php namespace Nine\Containers;

class ContextualBindingBuilder
{
    /**
     * The concrete instance.
     *
     * @var string
     */
    protected $concrete;

    /**
     * The underlying container instance.
     *
     * @var Container
     */
    protected $container;

    /**
     * The abstract target.
     *
     * @var string
     */
    protected $needs;

    /**
     * Create a new contextual binding builder.
     *
     * @param  Container $container
     * @param  string    $concrete
     */
    public function __construct(Container $container, $concrete)
    {
        $this->concrete = $concrete;
        $this->container = $container;
    }

    /**
     * Define the implementation for the contextual binding.
     *
     * @param  \Closure|string $implementation
     *
     * @return void
     */
    public function give($implementation)
    {
        $this->container->addContextualBinding($this->concrete, $this->needs, $implementation);
    }

    /**
     * Define the abstract target that depends on the context.
     *
     * @param  string $abstract
     *
     * @return ContextualBindingBuilder
     */
    public function needs($abstract) : ContextualBindingBuilder
    {
        $this->needs = $abstract;

        return $this;
    }
}
