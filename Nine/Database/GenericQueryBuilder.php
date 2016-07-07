<?php namespace Nine\Database;

use Opulence\QueryBuilders\DeleteQuery;
use Opulence\QueryBuilders\InsertQuery;
use Opulence\QueryBuilders\QueryBuilder;
use Opulence\QueryBuilders\SelectQuery;
use Opulence\QueryBuilders\UpdateQuery;
use ReflectionClass;

/**
 * A generic query builder linked to Opulence QueryBuilder class.
 *
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */
class GenericQueryBuilder extends QueryBuilder
{
    /**
     * @inheritdoc
     */
    public function delete(string $tableName, string $alias = '') : DeleteQuery
    {
        return new DeleteQuery($tableName, $alias);
    }

    /**
     * @inheritdoc
     */
    public function insert(string $tableName, array $columnNamesToValues) : InsertQuery
    {
        return new InsertQuery($tableName, $columnNamesToValues);
    }

    /**
     * @inheritdoc
     * @return SelectQuery
     */
    public function select(...$expression) : SelectQuery
    {
        // This code allows us to pass a variable list of parameters to a class constructor
        $queryClass = new ReflectionClass(SelectQuery::class);

        return $queryClass->newInstanceArgs($expression);
    }

    /**
     * @inheritdoc
     */
    public function update(string $tableName, string $alias, array $columnNamesToValues) : UpdateQuery
    {
        return new UpdateQuery($tableName, $alias, $columnNamesToValues);
    }
}
