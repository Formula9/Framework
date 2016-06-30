<?php namespace Nine\Database;

/**
 * F9 (Formula 9) Personal PHP Framework
 *
 * Copyright (c) 2010-2016, Greg Truesdell (<odd.greg@gmail.com>)
 * License: MIT (reference: https://opensource.org/licenses/MIT)
 *
 * Acknowledgements:
 *  - The code provided in this file (and in the Framework in general) may include
 * open sourced software licensed for the purpose, refactored code from related
 * packages, or snippets/methods found on sites throughout the internet.
 *  - All originator copyrights remain in force where applicable, as well as their
 *  licenses where obtainable.
 *
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

use Aura\Sql\ExtendedPdo;
use Nine\Collections\Collection;
use Nine\DatabaseInterface;
use PDO;

/**
 * **Database encapsulates PDO and Aura ExtendedPdo.**
 */
class Database implements DatabaseInterface
{
    /** @var string - the current Data Source Name */
    protected $current_dsn;

    /** @var array */
    private $db_config;

    /** @var string */
    private $db_default;

    /** @var int $db_fetch */
    private $db_fetch;

    /** @var PDO */
    protected static $PDO;

    /**
     * **Construct the database classes based on the settings generated by
     * DatabaseServiceProvider.**
     *
     * @param $db_config - an array of database configurations (see config/database.php)
     *
     * @see DatabaseServiceProvider
     */
    public function __construct($db_config)
    {
        $this->db_config = $db_config;
        $this->db_fetch = $this->db_config['fetch'];
        $this->db_default = $db_config['connections'][$this->db_config['default']];

        static::$PDO = $this->bootPDO();
    }

    /**
     * Get the PDO connection.
     */
    public function PDO()
    {
        return static::$PDO;
    }

    /**
     * **Boots the PDO using the Aura ExtendedPdo enhancement package.**
     *
     * @return \Aura\Sql\ExtendedPdo|null|PDO
     */
    public function bootPDO()
    {
        $default = $this->db_default;

        if ($default['driver'] === 'sqlite') {

            $this->current_dsn = "{$default['driver']}:{$default['database']}";
            $PDO = new ExtendedPdo($this->current_dsn, [], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            return $PDO;
        }

        $this->current_dsn = "{$default['driver']}:host={$default['host']};dbname={$default['database']}";
        $PDO = new PDO(
            $this->current_dsn,
            $default['username'],
            $default['password']
        );

        $PDO->setAttribute(PDO::ATTR_EMULATE_PREPARES, TRUE);
        $PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $PDO->setAttribute(PDO::ATTR_PERSISTENT, TRUE);
        $PDO->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, $this->db_fetch);

        # extend using Aura\SQL PDO extension
        $PDO = new ExtendedPdo($PDO);

        return $PDO;
    }

    /**
     * **Describe a table**
     *
     * @param string $table_name
     * @param bool   $detailed - true returns detailed field description
     *
     * @return array
     */
    public function describe($table_name, $detailed = FALSE) : array
    {
        /** @var \PDOStatement $query */
        $query = static::$PDO->query("DESCRIBE $table_name");

        return $query->fetchAll($detailed ? PDO::FETCH_ASSOC : PDO::FETCH_COLUMN);
    }

    /**
     * **Return the current DSN.**
     *
     * @return string
     */
    public function getCurrentDSN() : string
    {
        return $this->current_dsn;
    }

    /**
     * **Table `delete from` method.**
     *
     * @param string $table
     * @param string $where
     *
     * @return integer Count of affected rows
     */
    public function query_delete($table, $where) : int
    {
        return static::$PDO->exec("delete from $table where $where");
    }

    /**
     * **Wraps table insert queries.**
     *
     * @param string $table A name of table to insert into
     * @param array  $data  An associative array
     *
     * @return bool TRUE if successful, FALSE if not
     */
    public function query_insert($table, $data) : bool
    {
        ksort($data);

        $fieldNames = implode('`, `', array_keys($data));
        $fieldValues = ':' . implode(', :', array_keys($data));

        /** @var \PDOStatement $result */
        $result = static::$PDO->prepare("insert into $table (`$fieldNames`) values ($fieldValues)");
        foreach ($data as $key => $value) {
            $result->bindValue(":$key", $value);
        }

        return $result->execute();
    }

    /**
     * **Return the count of records in a given table.**
     *
     * @param $table_name
     *
     * @return int
     */
    public function query_record_count($table_name) : int
    {
        $result = $this->query_select("select count(*) as records from $table_name");

        if (count($result) > 0) {
            return $result[0]['records'];
        }
        else {
            return 0;
        }
    }

    /**
     * **Wraps a SQL query. Uses Prepare to handle variable values safely.**
     *
     * @param string $sql       An SQL string
     * @param array  $fields    Optional parameters to bind
     * @param int    $fetchMode A PDO Fetch mode
     *
     * @return Collection
     */
    public function query_select($sql, $fields = NULL, $fetchMode = NULL) : Collection
    {
        $result = static::$PDO->prepare($sql);

        if ($fields) {
            foreach ($fields as $key => $value) {
                $result->bindValue("$key", $value);
            }
        }

        $result->execute();

        return new Collection($result->fetchAll($fetchMode ?: $this->db_fetch));
    }

    /**
     * **Handles Table Update queries.**
     *
     * @param string $table A name of table to insert into
     * @param array  $data  An associative array
     * @param string $where the WHERE query part
     *
     * @return int number of rows updated
     */
    public function query_update($table, $data, $where) : int
    {
        ksort($data);
        $fieldDetails = '';

        foreach ($data as $key => $value) {
            $fieldDetails .= "`$key`=:$key,";
        }

        $fieldDetails = rtrim($fieldDetails, ',');

        /** @var \PDOStatement $result */
        $result = static::$PDO->prepare("update $table set $fieldDetails where $where");

        foreach ($data as $key => $value) {
            $result->bindValue(":$key", $value);
        }

        return $result->execute();
    }

    /**
     * **Gets the list of registered Tables.**
     *
     * @return array - array listing of tables in database
     */
    public function tables() : array
    {
        $tables = NULL;

        /** @var \PDOStatement $query */
        $query = static::$PDO->query('show tables');

        if ($query) {
            $tables = $query->fetchAll(PDO::FETCH_COLUMN);
        }

        return $tables;
    }

    /**
     * Return the current PDO reference.**
     *
     * @return null|PDO
     */
    public static function getPDO() : PDO
    {
        return static::$PDO;
    }

}
