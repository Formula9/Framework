<?php namespace Nine\Database;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

use Aura\Sql\ExtendedPdo;
use Nine\Collections\Collection;
use Nine\Exceptions\DBConnectionFailed;
use Nine\Exceptions\DBConnectionNotFound;
use Opulence\QueryBuilders\QueryBuilder;
use PDO;
use PDOStatement;

class NineBase
{
    const QUERY_TYPES = ['delete', 'insert', 'select', 'update'];

    /** @var PDO */
    protected $connection;

    /** @var string */
    protected $connectionName;

    /** @var QueryBuilder */
    protected $queryBuilder;

    /** @var string|DBQueryInterface */
    protected $sql;

    /** @var PDOStatement */
    protected $statement;

    /** @var Connections */
    private $connections;

    public function __construct(Connections $connections)
    {
        $this->connections = $connections;
    }

    public function build()
    {
        if (NULL === $this->connection) {
            throw new DBConnectionNotFound('Cannot build a query. No connection found.');
        }

        return $this->queryBuilder;
    }

    /**
     * @param int               $fetch
     * @param PDOStatement|NULL $statement If the statement is NULL then use the statement from the last query
     *
     * @return Collection
     * @throws DBInvalidStatement
     */
    public function collect($fetch = PDO::FETCH_ASSOC, PDOStatement $statement = NULL)
    {
        // use last statement if none is passed
        $statement = $statement ?: $this->statement;
        // use FETCH_ASSOC if null or other falsey is passed (an odd event indeed)
        $fetch = $fetch ?: PDO::FETCH_ASSOC;

        // validate the statement
        if (NULL === $statement or ! $statement instanceof PDOStatement) {
            throw new DBInvalidStatement('No valid statement was provided or found.');
        }

        return new Collection($statement->fetchAll($fetch));
    }

    /**
     * Connect to a named connection.
     *
     * All queries will operate on the last opened connection.
     *
     * @param string $connectionName
     *
     * @return NineBase
     * @throws DBConnectionFailed
     * @throws DBConnectionNotFound
     */
    public function connect(string $connectionName) : NineBase
    {
        // invalidate the current connection and related parameters
        $this->connection = NULL;
        $this->connectionName = '';
        $this->queryBuilder = NULL;

        // either opens the connection or retrieves it from cache.
        // also, will throw an exception id the connection doesn't exist
        // or a problem was encountered while connecting to the data source.
        $this->connection = $this->connections->getConnection($connectionName);
        $this->connectionName = $connectionName;

        // build a query factory for the connection driver type
        switch ($this->connections->getConnectionSettings($connectionName)['driver']) {
            case 'mysql':
                // use MySql
                $this->queryBuilder = new \Opulence\QueryBuilders\MySql\QueryBuilder();
                break;
            case 'pgsql':
                // use PostgreSql
                $this->queryBuilder = new \Opulence\QueryBuilders\PostgreSql\QueryBuilder();
                break;
            default :
                // generic
                $this->queryBuilder = new GenericQueryBuilder();
                break;
        }

        return $this;
    }

    /**
     *  Closes and clears the connection and related parameters;
     */
    public function disconnect()
    {
        $this->connections->closeConnection($this->connectionName);
        $this->connection = NULL;
        $this->connectionName = NULL;
        $this->sql = NULL;
        $this->statement = NULL;
    }

    /**
     * Executes a query and returns the resultant statement without fetching.
     *
     * This is useful for a lot of purposes - including using it with collect().
     *
     * Examples:
     *
     *      $db->connect('default')->execute('select * from users')->collect();
     *
     *      $db->connect('default')->execute($db->build('select')->from('users')->cols(['*']))->collect();
     *
     *      $db->connect('default')->execute('select * from users');
     *      $stmt = $db->getStatement();
     *      $db->collect($stmt);
     *
     * @param DBQueryInterface|string $sql
     * @param array                   $values
     *
     * @return NineBase
     */
    public function execute($sql, array $values = []) : NineBase
    {
        $this->query_sql($sql, $values);

        return $this;
    }

    /**
     * @return PDO|ExtendedPdo|null
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
        return ! (NULL === $this->connectionName) ? $this->connectionName : '';
    }

    /**
     * @return Connections
     */
    public function getConnections() : Connections
    {
        return $this->connections;
    }

    /**
     * @return string|DBQueryInterface
     */
    public function getSql()
    {
        return $this->sql;
    }

    /**
     * @return PDOStatement
     */
    public function getStatement()
    {
        return $this->statement;
    }

    /**
     * Returns either all results as a Collection or only the first result.
     *
     * Use `getStatement()` immediately following the query to get the PDOStatement.
     *
     * @param DBQueryInterface|string $sql
     * @param array                   $values
     * @param bool                    $all
     *
     * @return Collection|array
     */
    public function query($sql, array $values = [], bool $all = FALSE)
    {
        $this->sql = $sql;
        $this->statement = $this->query_sql($sql, $values);

        $this->statement->rowCount();

        if ($all) {
            return new Collection($this->statement->fetchAll(PDO::FETCH_ASSOC));
        }

        return $this->statement->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Returns the results of the query as a Collection.
     *
     * @param DBQueryInterface|string $sql
     * @param                         $values
     *
     * Example using the query builder:
     *
     * `$result = $db->connect('default')->queryAll($db->build('select')->from('users')->cols(['name','email'])->where('id = 1'))`
     *
     * returns a Collection of one or more records.
     *
     * @return Collection
     */
    public function queryAll($sql, array $values = [])
    {
        return $this->query($sql, $values, TRUE);
    }

    /**
     * @param DBQueryInterface|string $sql
     * @param                         $values
     *
     * @return array
     */
    public function queryFirst($sql, $values)
    {
        if ($sql instanceof DBQueryInterface) {
            $sql = $sql->getStatement();
            $sql .= ' limit 1';
        }

        return $this->query($sql, $values);
    }

    /**
     * @param DBQueryInterface|string $sql
     * @param array                   $values
     *
     * @return PDOStatement
     */
    protected function query_sql($sql, array $values) : PDOStatement
    {
        if (is_string($sql)) {
            $this->statement = $this->connection->prepare($sql);
            $this->statement->execute($values);
        }
        else {
            $sql->bindValues($values);
            $this->statement = $this->connection->prepare($sql->getStatement());
            $this->statement->execute($sql->getBindValues());
        }

        return $this->statement;
    }

}
