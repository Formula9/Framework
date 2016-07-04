<?php namespace Nine\Database\QueryBuilder;

use Aura\SqlQuery\QueryFactory;

/**
 * Builder provides a method for creating SQL queries targeted at
 * specific database driver types.
 *
 * Built on Aura/SqlQuery components, Builder simplifies query generation
 * by encapsulating and condensing features provided by Aura/SqlQuery.
 *
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */
class Builder
{
    /** @var QueryFactory */
    protected $query_factory;

    public function __construct(string $driver_name)
    {
        $this->query_factory = new QueryFactory($driver_name);
    }

    public function insert(string $table, $bindings)
    {
        $insert = $this->query_factory->newInsert();
        $insert->into($table)->cols($bindings);

        return $insert;
    }

    public function select(string $table, $columns = ['*'])
    {
        $select = $this->query_factory->newSelect();
        $select->from($table)->cols($columns);

        return $select;
    }

}
