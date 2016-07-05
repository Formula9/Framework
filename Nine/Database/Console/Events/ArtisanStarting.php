<?php

namespace Nine\Console\Events;

class ArtisanStarting
{
    /**
     * The Artisan application instance.
     *
     * @var \Illuminate\Console\Application
     */
    public $artisan;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Console\Application $artisan
     *
     */
    public function __construct($artisan)
    {
        $this->artisan = $artisan;
    }
}
