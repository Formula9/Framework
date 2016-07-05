<?php

namespace Illuminate\Console\Events;

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
     * @param  $artisan
     *
     */
    public function __construct($artisan)
    {
        $this->artisan = $artisan;
    }
}
