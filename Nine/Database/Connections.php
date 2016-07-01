<?php namespace Nine\Database;

use Aura\Sql\ExtendedPdo;
use Illuminate\Database\Connectors\ConnectionFactory;
use Nine\Exceptions\DBConnectionNotFound;

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

    /** @var ConnectionFactory */
    protected $factory;

    public function __construct(ConnectionFactory $factory, array $config = [])
    {
        // $connections must be an array of the form:
        //  [
        //      'default' => [
        //          'driver'    => 'mysql', ...
        //      ],
        //      etc.
        //  ]
        // ie: $connections = new Connections(config('database.connections')
        $this->config = $config;
        $this->factory = $factory;
    }

    /**
     * @param string $connection_name
     *
     * @return \PDO
     * @throws DBConnectionNotFound
     */
    public function getConnection(string $connection_name = 'default') : \PDO
    {
        // fail if the connection name cannot be found
        if ( ! isset($this->config[$connection_name]) and ! isset($this->cache[$connection_name])) {
            throw new DBConnectionNotFound("Connection name `$connection_name` not found.");
        }

        // return the active connection if it is in the cache
        if (isset($this->cache[$connection_name])) {
            return $this->cache[$connection_name];
        }

        // connect and cache the connection
        $this->cache[$connection_name] =
            new ExtendedPdo($this->factory->createConnector($this->config[$connection_name])
                                          ->connect($this->config[$connection_name]));

        // return the active connection
        return $this->cache[$connection_name];

    }

}
