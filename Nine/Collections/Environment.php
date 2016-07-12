<?php namespace Nine\Collections;

use Dotenv\Dotenv;
use Nine\Collections\Exceptions\InvalidEnvironmentKeyException;
use Nine\Collections\Interfaces\EnvironmentInterface;
use function env;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */
class Environment implements EnvironmentInterface
{
    /** @var array */
    private $detectedEnvironment;

    /**
     * @var Dotenv
     */
    private $env;

    /** @var string $environment - environment token. */
    private $environment;

    /** @var string */
    private $environmentKey;

    /**
     * Environment constructor.
     *
     * @param Dotenv $env
     * @param string $key
     */
    public function __construct(Dotenv $env, string $key = 'APP_ENV')
    {
        $this->env = $env;
        $this->environmentKey = $key;
        $this->environment = env($key);
    }

    /**
     * Get the current environment settings.
     *
     * > note: uses env() function from 'Nine/Library/helpers.php'.
     *
     * @return array - the environment settings data.
     * @throws InvalidEnvironmentKeyException
     */
    public function detectEnvironment() : array
    {
        if ( ! $this->has($this->environmentKey)) {
            throw new InvalidEnvironmentKeyException("Base environment setting ({$this->environmentKey}) not found.");
        }

        return $this->detectedEnvironment = [
            'developing' => env('APP_ENV', 'PRODUCTION') !== 'PRODUCTION',
            'app_key'    => env('APP_KEY', '$invalid$this&key%must#be@changed'),
            'debugging'  => env('DEBUG', FALSE),
            'testing'    => env('TESTING', FALSE),
        ];
    }

    /**
     * Get an item from storage by key.
     *
     * @param string $key - environment key; use '*' to get the entire environment.
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get(string $key, $default = NULL)
    {
        return $key === '*' ? $this->detectedEnvironment : env(strtoupper($key), $default);
    }

    /**
     * Determine if an item exists by key.
     *
     * @param  mixed $key
     *
     * @return bool
     */
    public function has($key)
    {
        return NULL !== env(strtoupper($key), NULL);
    }

}
