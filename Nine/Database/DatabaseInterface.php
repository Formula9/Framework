<?php namespace Nine\Database;

use Nine\Collections\Collection;
use PDO;

/**
 * **Database encapsulates PDO and Aura ExtendedPdo.**
 */
interface DatabaseInterface
{
    /**
     * **Describe a table**
     *
     * @param string $tableName
     *
     * @return array
     * @internal param bool $detailed - true returns detailed field description
     *
     */
    public function describe($tableName) : array;

    /**
     * **Table `delete from` method.**
     *
     * @param string $table
     * @param string $where
     *
     * @return integer Count of affected rows
     */
    public function queryDelete($table, $where) : int;

    /**
     * **Wraps table insert queries.**
     *
     * @param string $table A name of table to insert into
     * @param array  $data  An associative array
     *
     * @return bool TRUE if successful, FALSE if not
     */
    public function queryInsert($table, $data) : bool;

    /**
     * **Wraps a SQL query. Uses Prepare to handle variable values safely.**
     *
     * @param string $sql       An SQL string
     * @param array  $fields    Optional parameters to bind
     * @param int    $fetchMode A PDO Fetch mode
     *
     * @return Collection
     */
    public function querySelect($sql, $fields = NULL, $fetchMode = NULL) : Collection;

    /**
     * **Handles Table Update queries.**
     *
     * @param string $table A name of table to insert into
     * @param array  $data  An associative array
     * @param string $where the WHERE query part
     *
     * @return int number of rows updated
     */
    public function queryUpdate($table, $data, $where) : int;

    /**
     * Return the current PDO reference.**
     *
     * @return null|PDO
     */
    public static function getPDO() : PDO;
}
