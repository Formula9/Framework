<?php namespace Nine\Database;

use Aura\SqlQuery\Common\DeleteInterface;
use Aura\SqlQuery\Common\InsertInterface;
use Aura\SqlQuery\Common\SelectInterface;
use Aura\SqlQuery\Common\UpdateInterface;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 * @method string getStatement()
 */
interface DBQueryInterface extends SelectInterface, DeleteInterface, UpdateInterface, InsertInterface
{
}
