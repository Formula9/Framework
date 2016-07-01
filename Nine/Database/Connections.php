<?php namespace Nine\Database;

use Aura\Sql\ExtendedPdo;
use Nine\Exceptions\DBConnectionNotFound;
use Nine\Library\Arrays;
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

    public function __construct(array $config = [])
    {
        // connections configuration
        $this->config = $config;
        // connection list
        $this->connections = $config['connections'];
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
     * Get a configuration attribute or return $default value if not found.
     *
     * @param string     $key
     * @param null|mixed $default
     *
     * @return null
     */
    public function getConfig(string $key, $default = NULL)
    {
        return Arrays::array_query($this->config, $key, $default);
    }

    /**
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

        $connection = $this->connections[$name];

        return $this->cache[$name] = $this->makeDriverConnection($connection['driver'], $connection);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function isCached(string $name) : bool
    {
        return isset($this->connections[$name]);
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
     * @param string $driver
     * @param array  $connection
     *
     * @return ExtendedPdo|PDO
     */
    private function makeDriverConnection(string $driver, array $connection)
    {
        if ($driver === 'sqlite') {

            $dsn = "{$connection['driver']}:{$connection['database']}";
            $PDO = new ExtendedPdo($dsn, [], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            return new ExtendedPdo($PDO);
        }

        $dsn = "{$driver}:host={$connection['host']};dbname={$connection['database']}";
        $PDO = new PDO(
            $dsn,
            $connection['username'],
            $connection['password']
        );

        $PDO->setAttribute(PDO::ATTR_EMULATE_PREPARES, TRUE);
        $PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $PDO->setAttribute(PDO::ATTR_PERSISTENT, $this->config['persistent']);
        $PDO->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, $this->config['fetch']);

        # extend using Aura\SQL PDO extension
        return new ExtendedPdo($PDO);

    }

}
