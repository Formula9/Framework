<?php namespace Nine\Containers;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

use Carbon\Carbon;
use F9\Exceptions\DependencyInstanceNotFound;
use F9\Support\Provider\PimpleDumpProvider;
use Nine\Exceptions\CollectionExportWriteFailure;
use Nine\Library\Lib;

trait PhpStormMeta
{
    /**
     * @return array
     * @throws CollectionExportWriteFailure
     * @throws DependencyInstanceNotFound
     */
    public static function makePhpStormMeta()
    {
        static::$instance ?: new static();

        // conveniences and declarations
        $app = static::$app;
        $self = static::$instance;
        $map = [];
        $code = '';

        // collect illuminate aliases
        $forge_aliases = static::getInstance()->aliases;

        /** @var PimpleDumpProvider $pds */
        $pds = static::find('pimple_dump_service');
        $pds->dump($app);

        // get Pimple keys and merge with aliases
        $keys = $app->keys();

        // fold in forge aliases that do not already exist in the $app.
        foreach ($forge_aliases as $abstract => $alias) {
            if (class_exists($abstract)) {
                isset($keys[$abstract]) ?: $keys[] = "\\$abstract";
                $map[] = "'$alias' instanceof \\$abstract,";
            }
        }

        // Iterate through the key list to collect registrations.
        foreach ($keys as $key) {
            // assume nothing
            $appKey = static::key_object_exists($key)
                ? $self->parseValue($app, $key)
                : self::parseKey($app, $key);

            // ignoring 'app' replications, add the new .phpstorm.meta entry.
            if ($appKey and $appKey !== '' and $key !== 'app') {
                $map[] = "'$key' instanceof \\$appKey,";
            }
        }

        // sort and build code segment
        $map = array_unique($map);
        sort($map);

        // compile the map
        foreach ($map as $entry) {
            $code .= '            ' . $entry . PHP_EOL;
        }

        $template = file_get_contents(__DIR__ . '/assets/meta.php.template');

        $template = str_replace('%%MAP%%', $code, $template);
        $template = str_replace('%%DATE%%', Carbon::now()->toDateTimeString(), $template);
        $template = str_replace('%%COUNT%%', count($map), $template);
        $template = str_replace('%%ID%%', Lib::generate_token(8, '$meta$'), $template);

        if (FALSE === file_put_contents(ROOT . '.phpstorm.meta.php', $template)) {
            throw new CollectionExportWriteFailure('Unable to update .phpstorm.meta.php.');
        }

        return $map;
    }

    /**
     * **Parse an item's type and value.**
     *
     * @param        $container
     * @param string $name
     *
     * @return array|null
     */
    protected function parseValue($container, $name)
    {
        try {
            $element = $container[$name];
        } catch (\Exception $e) {
            return NULL;
        }

        if (is_object($element)) {
            if ($element instanceof \Closure) {
                //$type = 'closure';
                $value = '';
            }
            elseif ($element instanceof Container) {
                //$type = 'class';
                $value = get_class($element); # $this->parseContainer($element);
            }
            else {
                //$type = 'class';
                $value = is_string($element) ? $element : get_class($element);
            }
        }
        elseif (is_array($element)) {
            //$type = 'array';
            $value = '';
        }
        elseif (is_string($element)) {
            //$type = 'string';
            $value = $element;
        }
        elseif (is_int($element)) {
            //$type = 'int';
            $value = $element;
        }
        elseif (is_float($element)) {
            //$type = 'float';
            $value = $element;
        }
        elseif (is_bool($element)) {
            //$type = 'bool';
            $value = $element;
        }
        elseif ($element === NULL) {
            //$type = 'null';
            $value = '';
        }
        else {
            //$type = 'unknown';
            $value = gettype($element);
        }

        return $value;
    }

    /**
     * @param $app
     * @param $key
     *
     * @return null|string
     */
    protected static function parseKey($app, $key)
    {
        $appValue = $app[$key];

        switch (gettype($appValue)) {
            case 'object':
                $appKey = get_class($appValue);
                break;
            case 'string':
                $appKey = class_exists($appValue) ? $appValue : NULL;
                break;
            default :
                $appKey = NULL;
                break;

        }

        return $appKey;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    private static function key_object_exists($key) : bool
    {
        $parent = parent::getInstance();

        return $parent->bound($key);
    }
}
