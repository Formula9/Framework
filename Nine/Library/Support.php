<?php namespace Nine\Library;

/**
 * Support is a library of methods for outside of arrays and strings.
 *
 * @package Nine Library
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

trait Support
{
    /**
     * **Generates and alias name for a class, optionally removing a suffix.**
     *
     * <pre>example:
     *      call:   alias_from_class('A\Namespace\ClassServiceProvider');
     *
     *      result: 'classserviceprovider'</pre>
     *
     * @param $class_name       - name of the class
     * @param $suffix_to_remove - suffix to strip from class name
     *
     * @return string
     */
    public static function alias_from_class($class_name, $suffix_to_remove = '')
    {
        return strtolower(static::remove_namespace($class_name, $suffix_to_remove));
    }

    /** @noinspection GenericObjectTypeUsageInspection
     *
     * **Typecast an array to an object.**
     *
     * Note: This is entirely redundant.
     *
     * @param $array
     *
     * @return object
     */
    public static function cast_array_to_object($array)
    {
        return (object) $array;
    }

    /**
     * **Typecast an object to an array.**
     *
     * Note: This is entirely redundant.
     *
     * @param $object
     *
     * @return array
     */
    public static function cast_object_as_array($object)
    {
        return (array) $object;
    }

    /**
     * **Get an item from an array or object using "dot" notation.**
     *
     * @param  mixed        $target
     * @param  string|array $key
     * @param  mixed        $default
     *
     * @return mixed
     */
    public static function data_get($target, $key, $default = NULL)
    {
        if (NULL === $key) {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.', $key);

        while (($segment = array_shift($key)) !== NULL) {
            if ($segment === '*') {
                if (is_object($target) and method_exists($target, 'toArray')) {
                    $target = $target->all();
                }
                elseif ( ! is_array($target)) {
                    return value($default);
                }

                $result = Lib::array_extract($target, $key);

                return in_array('*', $key, TRUE) ? Lib::collapse($result) : $result;
            }

            if (Lib::array_accessible($target) && Lib::key_exists($target, $segment)) {
                $target = $target[$segment];
            }
            elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            }
            else {
                return value($default);
            }
        }

        return $target;
    }

    /**
     * **Set an item on an array or object using dot notation.**
     *
     * @param  mixed        $target
     * @param  string|array $key
     * @param  mixed        $value
     * @param  bool         $overwrite
     *
     * @return mixed
     */
    public static function data_set(&$target, $key, $value, $overwrite = TRUE)
    {
        $segments = is_array($key) ? $key : explode('.', $key);

        if (($segment = array_shift($segments)) === '*') {
            /** @noinspection NotOptimalIfConditionsInspection */
            /** @noinspection ReferenceMismatchInspection */
            if ( ! Lib::array_accessible($target)) {
                $target = [];
            }

            if ($segments) {
                foreach ($target as &$inner) {
                    static::data_set($inner, $segments, $value, $overwrite);
                }
            }
            elseif ($overwrite) {
                /** @noinspection ReferenceMismatchInspection */
                foreach ($target as &$inner) {
                    $inner = $value;
                }
            }
        }
        /** @noinspection ReferenceMismatchInspection */
        elseif (Lib::array_accessible($target)) {
            if ($segments) {
                /** @noinspection ReferenceMismatchInspection */
                if ( ! Lib::key_exists($target, $segment)) {
                    $target[$segment] = [];
                }

                static::data_set($target[$segment], $segments, $value, $overwrite);
            }
            /** @noinspection ReferenceMismatchInspection */
            elseif ($overwrite || ! Lib::key_exists($target, $segment)) {
                $target[$segment] = $value;
            }
        }
        elseif (is_object($target)) {
            if ($segments) {
                if ( ! isset($target->{$segment})) {
                    $target->{$segment} = [];
                }

                static::data_set($target->{$segment}, $segments, $value, $overwrite);
            }
            elseif ($overwrite || ! isset($target->{$segment})) {
                $target->{$segment} = $value;
            }
        }

        return $target;
    }

    /**
     * **Searches a set of directories for the given filename.**
     *
     * @param string $name  name and extension part of file path
     * @param array  $paths array of folders to search
     *
     * @return string complete path to file
     */
    public static function file_in_path($name, Array $paths)
    {
        $file_path = FALSE;

        foreach ($paths as $path) {
            if (file_exists($path . $name)) {
                $file_path = $path . $name;
                break;
            }
        }

        return $file_path;
    }

    /**
     * **Fill an object from an array of qualified values.**
     *
     * @param $obj
     * @param $array
     *
     * @return mixed
     */
    public static function fill_object($obj, $array)
    {
        foreach ($array as $property => $value) {
            $obj->$property = $value;
        }

        return $obj;
    }

    /**
     * **Use `bin2hex` and `openssl_random_pseudo_bytes` to generate a unique token.**
     *
     * <pre>example 1:
     *
     *      call:   $lib->generate_token(32,'$salt$');
     *
     *      # note that the generated code will be different when you test this.
     *      result: "$salt$b3ae000938fbd25639edbaf83b41be300b9426a156625f9e3998f6ab441048a7"</pre>
     *
     * @param int    $length
     * @param string $preface
     *
     * @return string 32 character (16 bytes) string - unique for each run
     */
    public static function generate_token($length = 16, $preface = '')
    {
        $bh = bin2hex(openssl_random_pseudo_bytes($length));

        return "{$preface}{$bh}";
    }

    /**
     * **Accept a class or class name and return the class name.**
     *
     * On the surface, this seems like an odd idea. However, this function's
     * purpose is to always return a class name string -- whether the
     * arguments is already a string or a object.
     *
     * @param mixed $class
     *
     * @return string
     */
    public static function get_class_name($class)
    {
        return is_string($class) ? $class : get_class($class);
    }

    /**
     * **Converts a associative array of key,value pairs to SQL query comparisons.**
     *
     * ie: ['a' => 4, 'b' => 'open'] -> [0 => "a=`4`", 1 => "b=`open`"]
     *
     * @param array $array
     *
     * @return array|null
     */
    public static function make_compare(array $array)
    {
        $list = [];

        if (Lib::is_assoc($array)) {
            foreach ($array as $key => $value)
                $list[] = $key . '=`' . $value . '`';

            return $list;
        }

        return NULL;
    }

    /**
     * **Normalize a path by filtering through `realpath`.
     *
     * _Adds a trailing backslash._
     *
     * @param $path
     *
     * @return string
     */
    public static function normalize_path($path)
    {
        return Lib::strip_tail('/', realpath($path)) . '/';
    }

    /**
     * **Parse class name into namespace and class_name**
     *
     * _Modified version of a function found in the PHP docs._
     *
     * @param string $name
     *
     * @return array
     */
    public static function parse_class_name($name)
    {
        $namespace = array_slice(explode('\\', $name), 0, -1);

        return [
            'namespace'      => $namespace,
            'class_name'     => implode('', array_slice(explode('\\', $name), -1)),
            'namespace_path' => implode('\\', $namespace),
            'namespace_base' => $namespace[0] ?? '',
        ];
    }

    /**
     * **Removes the namespace from a class name.**
     *
     * @param string $class_name
     * @param string $class_suffix
     *
     * @return mixed
     */
    public static function remove_namespace($class_name, $class_suffix = NULL)
    {
        $segments = explode('\\', $class_name);
        $class = $segments[count($segments) - 1];
        if ( ! is_null($class_suffix)) {
            $class = str_ireplace($class_suffix, '', $class);
        }

        return $class;
    }

    /**
     * **Retrieves the value of an array element or object property with the given key or property name.**
     *
     * If the key does not exist in the array or object, the default value will be returned instead.
     *
     * The key may be specified in a dot format to retrieve the value of a sub-array or the property
     * of an embedded object. In particular, if the key is `x.y.z`, then the returned value would
     * be `$array['x']['y']['z']` or `$array->x->y->z` (if `$array` is an object). If `$array['x']`
     * or `$array->x` is neither an array nor an object, the default value will be returned.
     * Note that if the array already has an element `x.y.z`, then its value will be returned
     * instead of going through the sub-arrays.
     *
     * @param array|mixed $array       array or object to extract value from
     * @param string      $key         key name of the array element, or property name of the object,
     *                                 or an anonymous function returning the value. The anonymous function signature
     *                                 should be:
     *                                 `function($array, $defaultValue)`.
     * @param mixed       $default     the default value to be returned if the specified array key does not exist. Not
     *                                 used when getting value from an object.
     *
     * @return mixed the value of the element if found, default value otherwise
     */
    public static function value($array, $key, $default = NULL)
    {
        if ($key instanceof \Closure or is_callable($key)) {
            return $key($array, $default);
        }

        if (is_array($array) && array_key_exists($key, $array)) {
            return $array[$key];
        }

        if (($pos = strrpos($key, '.')) !== FALSE) {
            $array = static::value($array, substr($key, 0, $pos), $default);
            $key = substr($key, $pos + 1);
        }

        if (is_object($array)) {
            return $array->$key;
        }
        elseif (is_array($array)) {
            return array_key_exists($key, $array) ? $array[$key] : $default;
        }
        else {
            return $default;
        }
    }

    /**
     * **Return an array with the object name as the key.**
     *
     * @param       $object
     * @param mixed $value
     *
     * @return array|null
     */
    public static function value_class($object, $value)
    {
        if (is_object($object)) {
            $class = get_class($object);

            return [$class => $value];
        }

        return NULL;
    }

}
