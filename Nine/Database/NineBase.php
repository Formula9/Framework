<?php namespace Illuminate\Database;

use Nine\Database\Connections;
use Nine\Exceptions\DBConnectionFailed;
use Nine\Exceptions\DBConnectionNotFound;
use PDO;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */
class NineBase
{
    /** @var PDO */
    protected $connection;

    /** @var string */
    protected $connection_name;

    /** @var Connections */
    private $connections;

    public function __construct(Connections $connections)
    {
        $this->connections = $connections;
    }

    /**
     * Connect to a named connection.
     *
     * All queries will operate on the last opened connection.
     *
     * @param string $connection_name
     *
     * @return NineBase
     * @throws DBConnectionFailed
     * @throws DBConnectionNotFound
     */
    public function connect(string $connection_name) : static
    {
        // invalidate the current connection
        $this->connection = NULL;
        $this->connection_name = NULL;

        // either opens the connection or retrieves it from cache.
        // also, will throw an exception id the connection doesn't exist
        // or a problem was encountered while connecting to the data source.
        $this->connection = $this->connections->getConnection($connection_name);
        $this->connection_name = $connection_name;

        return $this;
    }

    public function disconnect()
    {
        $this->connections->closeConnection($this->connection_name);
        $this->connection = NULL;
        $this->connection_name = NULL;
    }

    /**
     * @return PDO|null
     */
    public function getConnection() : PDO
    {
        return $this->connection;
    }

    /**
     * @return string
     */
    public function getConnectionName() : string
    {
        return $this->connection_name !== NULL ?? '';
    }

    /**
     * @return Connections
     */
    public function getConnections() : Connections
    {
        return $this->connections;
    }

}
