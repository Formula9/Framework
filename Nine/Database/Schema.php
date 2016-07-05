<?php

/**
 * The Schema class implements the Illuminate facade of the same name by encapsulating
 * the connection created by the DatabaseServiceProvider ($app['db.connection']).
 *
 * Requires `DatabaseServiceProvider`.
 *
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Builder;
use Nine\Containers\Forge;

/** @noinspection SingletonFactoryPatternViolationInspection */
class Schema
{
    const VERSION = '0.1.0';

    /** @var \Illuminate\Database\Connection $connection */
    protected static $connection;

    /** @var static */
    protected static $instance;

    /** @var Builder */
    protected static $schema;

    public function __construct(Illuminate\Database\Connection $connection = NULL)
    {
        static::$connection = $connection ?: Forge::find('db.connection');
        static::$schema = static::$connection->getSchemaBuilder();

        static::$instance = $this;
    }

    /**
     * Set the Schema Blueprint resolver callback.
     *
     * @param  \Closure $resolver
     *
     * @return void
     */
    public function blueprintResolver(Closure $resolver)
    {
        static::$schema->blueprintResolver($resolver);
    }

    /**
     * Get the column listing for a given table.
     *
     * @param  string $table
     *
     * @return array
     */
    public function getColumnListing($table)
    {
        return static::$schema->getColumnListing($table);
    }

    /**
     * Get the data type for the given column name.
     *
     * @param  string $table
     * @param  string $column
     *
     * @return string
     */
    public function getColumnType($table, $column)
    {
        return static::$schema->getColumnType($table, $column);
    }

    /**
     * Get the database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    public function getConnection()
    {
        return static::$schema->getConnection();
    }

    /**
     * Determine if the given table has a given column.
     *
     * @param  string $table
     * @param  string $column
     *
     * @return bool
     */
    public function hasColumn($table, $column)
    {
        return static::$schema->hasColumn($table, $column);
    }

    /**
     * Determine if the given table has given columns.
     *
     * @param  string $table
     * @param  array  $columns
     *
     * @return bool
     */
    public function hasColumns($table, array $columns)
    {
        return static::$schema->hasColumns($table, $columns);
    }

    /**
     * Determine if the given table exists.
     *
     * @param  string $table
     *
     * @return bool
     */
    public function hasTable($table)
    {
        return static::$schema->hasTable($table);
    }

    /**
     * Rename a table on the schema.
     *
     * @param  string $from
     * @param  string $to
     *
     * @return \Illuminate\Database\Schema\Blueprint
     */
    public function rename($from, $to)
    {
        return static::$schema->rename($from, $to);
    }

    /**
     * Set the database connection instance.
     *
     * @param  \Illuminate\Database\Connection $connection
     *
     * @return $this
     */
    public function setConnection(Connection $connection)
    {
        return static::$schema->setConnection($connection);
    }

    /**
     * Create a new table on the schema.
     *
     * @param  string   $table
     * @param  \Closure $callback
     *
     * @return \Illuminate\Database\Schema\Blueprint
     */
    public static function create($table, Closure $callback)
    {
        static::$instance = static::$instance ?: new static();

        return static::$schema->create($table, $callback);
    }

    /**
     * Drop a table from the schema.
     *
     * @param  string $table
     *
     * @return \Illuminate\Database\Schema\Blueprint
     */
    public static function drop($table)
    {
        static::$instance = static::$instance ?: new static();

        return static::$schema->drop($table);
    }

    /**
     * Drop a table from the schema if it exists.
     *
     * @param  string $table
     *
     * @return \Illuminate\Database\Schema\Blueprint
     */
    public static function dropIfExists($table)
    {
        static::$instance = static::$instance ?: new static();

        return static::$schema->dropIfExists($table);
    }

    /**
     * Return a singleton instance.
     *
     * @return static
     */
    public static function getInstance()
    {
        return static::$instance = static::$instance ?: new static();
    }

    /**
     * Modify a table on the schema.
     *
     * @param  string   $table
     * @param  \Closure $callback
     *
     * @return \Illuminate\Database\Schema\Blueprint
     */
    public static function table($table, Closure $callback)
    {
        static::$instance = static::$instance ?: new static();

        return static::$schema->table($table, $callback);
    }

}
