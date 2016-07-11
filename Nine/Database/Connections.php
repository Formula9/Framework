<?php namespace Nine\Database;

use Aura\Sql\ExtendedPdo;
use Nine\Exceptions\DBCannotRemoveCachedConnection;
use Nine\Exceptions\DBConnectionNotFound;
use Nine\Exceptions\DBDuplicateConnection;
use Nine\Library\Lib;
use PDO;

/**
 * @package Nine
 * @version 0.1.0
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */
class Connections
{
    /** @var array simple connection cache */
    protected $cache = [];

    /** @var array */
    protected $config;

    public function __construct(array $config)
    {
        // connections configuration
        $this->config = $config;
        // connection list
        $this->connections = $config['connections'];
    }

    /**
     * @param string $name
     * @param array  $settings
     *
     * @throws DBDuplicateConnection
     */
    public function addConnection(string $name, array $settings)
    {
        if ($this->hasConnection($name)) {
            throw new DBDuplicateConnection("Connection already has `$name`. Duplicates are not allowed. Try removing first.");
        }

        $this->connections[$name] = $settings;
    }

    /**
     * Replaces or inserts alternate values.
     *
     * @param array $config
     *
     * @return array Returns the resultant config array
     */
    public function alterConfig(array $config) : array
    {
        return $this->config = array_replace_recursive($this->config, $config);
    }

    /**
     *  Closes all open connections and clears the cache.
     */
    public function clearCache()
    {
        $keys = array_keys($this->cache);

        foreach ($keys as $key) {
            unset($this->cache[$key]);
        }

        $this->cache = [];
    }

    /**
     * @param string $name
     */
    public function closeConnection(string $name)
    {
        if ($this->isCached($name)) {
            Lib::array_forget($this->cache, $name);
        }
    }

    /**
     * Get a configuration attribute or return $default value if not found.
     *
     * @param string     $key
     * @param null|mixed $default
     *
     * @return null
     */
    public function getConfig(string $key, $default = NULL)
    {
        return Lib::array_query($this->config, $key, $default);
    }

    /**
     * @param array $config
     */
    public function setConfig(array $config)
    {
        $this->config = [];
        $this->config = array_merge($this->config, $config);
    }

    /**
     * Gets a connection from the cache or opens a new one using the name as the key.
     *
     * If, for whatever reason, the DSN is not included in the connection settings,
     * then on a successful connection the DSN is copied and saved in the configuration.
     * This should probably be avoided.
     *
     * @param string $name
     *
     * @return PDO
     * @throws DBConnectionNotFound
     */
    public function getConnection(string $name = 'default') : PDO
    {
        // fail if the connection name cannot be found
        if ( ! isset($this->connections[$name]) and ! isset($this->cache[$name])) {
            throw new DBConnectionNotFound("Connection name `$name` not found.");
        }

        // return the active connection if it is in the cache
        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        // get the connection settings
        $connection = $this->connections[$name];
        // retrieve and cache the connection if successful
        $this->cache[$name] = $this->makeDriverConnection($connection['driver'], $connection);

        if ( ! array_key_exists('dsn', $this->connections[$name])) {
            $this->connections[$name]['dsn'] = $connection['dsn'];
            $this->config['connections'][$name]['dsn'] = $connection['dsn'];
        }

        return $this->cache[$name];
    }

    /**
     * @param string $name
     *
     * @return array
     * @throws DBConnectionNotFound
     */
    public function getConnectionSettings(string $name) : array
    {
        if ( ! $this->hasConnection($name)) {
            throw new DBConnectionNotFound("Connection `$name` does not exist.");
        }

        return $this->config['connections'][$name];
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasConnection(string $name) : bool
    {
        return isset($this->connections[$name]);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function isCached(string $name) : bool
    {
        return isset($this->cache[$name]);
    }

    /**
     * @param string $name
     *
     * @throws DBCannotRemoveCachedConnection
     */
    public function removeConnection(string $name)
    {
        if ($this->isCached($name)) {
            throw new DBCannotRemoveCachedConnection("Connection `$name` is active and therefore cannot be removed.");
        }

        if ($this->hasConnection($name)) {
            Lib::array_forget($this->connections, $name);
        }
    }

    /**
     * @param string $driver
     * @param array  $connection
     *
     * @return ExtendedPdo|PDO
     */
    private function makeDriverConnection(string $driver, array &$connection)
    {
        if ($driver === 'sqlite') {

            $dsn = "{$connection['driver']}:{$connection['database']}";
            $PDO = new ExtendedPdo($dsn, NULL, NULL, [], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            // register the dsn if not already registered
            if ( ! isset($connection['dsn'])) {
                $connection['dsn'] = $dsn;
            }

            return new ExtendedPdo($PDO);
        }

        $dsn = $connection['dsn'] ?? "{$driver}:host={$connection['host']};dbname={$connection['database']}";

        $PDO = new PDO(
            $dsn,
            $connection['username'],
            $connection['password']
        );

        // register the dsn if not already registered
        if ( ! isset($connection['dsn'])) {
            $connection['dsn'] = $dsn;
        }

        $PDO->setAttribute(PDO::ATTR_EMULATE_PREPARES, TRUE);
        $PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $PDO->setAttribute(PDO::ATTR_PERSISTENT, $this->config['persistent']);
        $PDO->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, $this->config['fetch']);

        # extend using Aura\SQL PDO extension
        return new ExtendedPdo($PDO);

    }

}
