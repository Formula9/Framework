<?php namespace Nine\Exceptions;

/**
 * **This exception is thrown when an object or process attempts accessing
 * the Database, Eloquent or PDO classes before they are properly configured.**
 */
class DBDatabaseLayerNotFound extends \Exception
{

}
