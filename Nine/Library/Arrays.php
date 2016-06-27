<?php namespace Nine\Library;

/**
 * @package Nine Library
 * @version 0.3.1
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

use ArrayAccess;

/**
 * **Arrays is a compendium of array functions, supplied as static methods,
 * collected from a number of OSS sources or created for the project.**
 *
 * Several methods are included for compatibility (often as pseudonyms)
 * with imported or included packages, such as illuminate packages.
 */
trait Arrays
{
    /**
     * **Determine whether the given value is array accessible.**
     *
     * @param  mixed $value
     *
     * @return bool
     */
    public static function array_accessible($value)
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }

    /**
     * **Build a new array using a callback.**
     *
     * @param  array    $array
     * @param  callable $callback
     *
     * @return array
     */
    public static function array_build($array, callable $callback)
    {
        $results = [];

        foreach ($array as $key => $value) {
            list($innerKey, $innerValue) = call_user_func($callback, $key, $value);

            $results[$innerKey] = $innerValue;
        }

        return $results;
    }

    /**
     * **Collapse an array of arrays into a single array.**
     *
     *  <pre>
     *      given:   [[1,2,3],[4,5,6],[7,8,9]]
     *
     *      result:  [1,2,3,4,5,6,7,8,9]</pre>
     *
     * @param  array|\ArrayAccess $array
     *
     * @return array
     */
    public static function array_collapse($array)
    {
        $results = [];

        foreach ($array as $values) {
            /** @noinspection SlowArrayOperationsInLoopInspection */
            $results = array_merge($results, $values);
        }

        return $results;
    }

    /**
     * **Get all of the given array except for a specified array of items.**
     *
     * <pre>
     * given:   array_except(['two','five'], ['one'=>1,'two'=>2,'three'=>3,'four'=>4,'five'=>5,'six'=>6]);
     *
     * result:  [
     *              'one'   => 1,
     *              'three' => 3,
     *              'four'  => 4,
     *              'six'   => 6,
     *           ]</pre>
     *
     * @param  array        $array
     *
     * @param  array|string $keys
     *
     * @return array
     */
    public static function array_except($array, $keys)
    {
        return array_diff_key($array, array_flip((array) $keys));
    }

    /**
     * **Transforms a single dimension array to a key:pair associative array.**
     *
     * Each element of the source is transformed to `<value> => <default>`.
     *
     * <pre>
     * given:   $test = ['a','b','c','d','e']
     *
     * call:    var_export(Lib::array_transform($test, ''))
     *
     * result:  array ('a'=>'','b'=>'','c'=>'','d'=>'','e'=>'',)</pre>
     *
     * @param array      $source
     * @param null|mixed $default
     *
     * @return array
     */
    public static function array_expand(array $source, $default = NULL)
    {
        $transform = [];
        foreach ($source as $var)
            $transform[$var] = $default;

        return $transform;
    }

    /**
     * **Pluck an array of values from an array.**
     *
     * @param  array             $array
     * @param  string|array      $value
     * @param  string|array|null $key
     *
     * @return array
     */
    public static function array_extract($array, $value, $key = NULL)
    {
        $results = [];

        list($value, $key) = static::explode_extract_parameters($value, $key);

        foreach ($array as $item) {
            $itemValue = Support::data_get($item, $value);

            // If the key is "null", we will just append the value to the array and keep
            // looping. Otherwise we will key the array using the value of the key we
            // received from the developer. Then we'll return the final array form.
            if (NULL === $key) {
                $results[] = $itemValue;
            }
            else {
                $itemKey = Support::data_get($item, $key);

                $results[$itemKey] = $itemValue;
            }
        }

        return $results;
    }

    /**
     * **Fetch a flattened array of a nested array element.**
     *
     * <pre>
     * given:   $test = [['one' => 1, 'two' => 2], ['three' => 3, 'four' => 4], ['five' => 5, 'six' => 6]]
     *
     * call:    array_fetch($test, 'five')
     *
     * result:  [5]</pre>
     *
     * @param  array  $array
     * @param  string $key
     *
     * @return array
     */
    public static function array_fetch($array, $key)
    {
        $results = [];

        foreach (explode('.', $key) as $segment) {

            foreach ($array as $value) {
                if (array_key_exists($segment, $value = (array) $value)) {
                    $results[] = $value[$segment];
                }
            }

            /** @noinspection DisconnectedForeachInstructionInspection */
            $array = array_values($results);
        }

        return array_values($results);
    }

    /**
     * **Return the first matching value element in an array passing a given truth test.**
     *
     * <pre>
     * given:   $test = ['a'=>[1,2],'b'=>[3,4]];
     *
     * call:    var_export(array_first($test, function ($key, $value) {
     *              return array_sum($value) === 7;
     *          }));
     *
     * result   [3,4]</pre>
     *
     * @param  array    $haystack
     * @param  callable $callback
     * @param  mixed    $default
     *
     * @return mixed
     */
    public static function array_first_match($haystack, callable $callback, $default = NULL)
    {
        foreach ($haystack as $key => $value) {
            if ($callback($key, $value)) {
                return $value;
            }
        }

        return value($default);
    }

    /**
     * **Flatten a multi-dimensional associative array with dot notation.**
     *
     * <pre>
     * example 1:
     * given:   ['first' =>['one'=>1,'two'=>2],'second'=>['three'=>3,'four'=>4],'third'=>['five'=>5,'six'=>6]]
     * result   [
     *              'first.one'     => 1,
     *              'first.two'     => 2,
     *              'second.three'  => 3,
     *              'second.four'   => 4,
     *              'third.five'    => 5,
     *              'third.six'     => 6,
     *          ]
     *
     * example 2:
     * given:    [['one'=>1,'two'=>2],['three'=>3,'four'=>4],['five'=>5,'six'=>6]]
     * result   [
     *              '0.one'     => 1,
     *              '0.two'     => 2,
     *              '1.three'   => 3,
     *              '1.four'    => 4,
     *              '2.five'    => 5,
     *              '2.six'     => 6,
     *          ]</pre>
     *
     * @param  array  $array
     * @param  string $prepend
     *
     * @return array
     */
    public static function array_flatten($array, $prepend = '')
    {
        $results = [];

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $results = array_merge($results, static::array_flatten($value, $prepend . $key . '.'));
            }
            else {
                $results[$prepend . $key] = $value;
            }
        }

        return $results;
    }

    /**
     * **Remove one or many array items from a given array using "dot" notation.**
     *
     * <pre>
     * given:   $test = ['a'=>['one'=>1,'two'=>2],'b'=>['three'=>3,'four'=>4]];
     *
     * call:    array_forget('a.two', $test);
     *
     * result:  [
     *              'a' =>
     *                  [
     *                      'one' => 1,
     *                  ],
     *              'b' =>
     *                  [
     *                      'three' => 3,
     *                      'four' => 4,
     *                  ],
     *            ]</pre>
     *
     * @param  array        $array
     * @param  array|string $keys
     */
    public static function array_forget(&$array, $keys)
    {
        $original = &$array;

        foreach ((array) $keys as $key) {
            $parts = explode('.', $key);

            while (count($parts) > 1) {
                $part = array_shift($parts);

                if (isset($array[$part]) && is_array($array[$part])) {
                    $array = &$array[$part];
                }
            }

            unset($array[array_shift($parts)]);

            // clean up after each pass
            $array = &$original;
        }
    }

    /**
     * **Recursively copy object properties to an array.**
     *
     * @note Only copies properties accessible in the current scope.
     *
     * @param $obj
     *
     * @return array
     */
    public static function array_from_object($obj)
    {
        $result = [];
        foreach (get_object_vars($obj) as $key => $value) {
            if (is_object($value)) {
                $value = self::array_from_object($value);
            }
            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * **Converts a string of space or tab delimited words as an array.**
     *
     * Multiple whitespace between words is converted to a single space.
     *
     * @param        $words
     * @param string $delimiter
     *
     * @return array
     */
    public static function array_from_string($words, $delimiter = ' ')
    {
        return explode($delimiter, preg_replace('/\s+/', ' ', $words));
    }

    /**
     * **Get an item from an array using "dot" notation.**
     *
     * @param  array  $array
     * @param  string $key
     * @param  mixed  $default
     *
     * @return mixed
     */
    public static function array_get($array, $key, $default = NULL)
    {
        if (NULL === $key) {
            return $array;
        }

        if (isset($array[$key])) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if ( ! is_array($array) || ! array_key_exists($segment, $array)) {
                return $default;
            }

            $array = $array[$segment];
        }

        return $array;
    }

    /**
     * **Check if an item exists in an array using "dot" notation.**
     *
     * @param  array  $array
     * @param  string $key
     *
     * @return bool
     */
    public static function array_has($array, $key)
    {
        if ([] === $array || NULL === $key) {
            return FALSE;
        }

        if (array_key_exists($key, $array)) {
            return TRUE;
        }

        foreach (explode('.', $key) as $segment) {
            if ( ! is_array($array) || ! array_key_exists($segment, $array)) {
                return FALSE;
            }

            $array = $array[$segment];
        }

        return TRUE;
    }

    /**
     * **Indexes an array according to a specified key.**
     *
     * The input array should be multidimensional or an array of objects.
     *
     * The key can be a key name of the sub-array, a property name of object, or an anonymous
     * function which returns the key value given an array element.
     *
     * If a key value is null, the corresponding array element will be discarded and not put in the result.
     *
     * given:   `$array = [
     *              ['id' => '123', 'data' => 'abc'],
     *              ['id' => '345', 'data' => 'def'],
     *          ];`
     * call:    `array_index($array, 'id');`
     * result:  `[
     *              '123' => ['id' => '123', 'data' => 'abc'],
     *              '345' => ['id' => '345', 'data' => 'def'],
     *          ]`
     *
     * Using an anonymous function:
     *      `$result = array_index($array, function ($element) {
     *          return $element['id'];
     *      });`
     *
     * @param array           $array the array that needs to be indexed
     * @param string|\Closure $key   the column name or anonymous function whose result will be used to index the array
     *
     * @return array the indexed array
     */
    public static function array_index($array, $key)
    {
        $result = [];
        foreach ($array as $element) {
            $value = Support::value($element, $key);
            $result[$value] = $element;
        }

        return $result;
    }

    /**
     * **Return the last element in an array passing a given truth test.**
     *
     * @param  array    $array
     * @param  callable $callback
     * @param  mixed    $default
     *
     * @return mixed
     */
    public static function array_last($array, callable $callback, $default = NULL)
    {
        return static::array_first_match(array_reverse($array), $callback, $default);
    }

    /**
     * **Get a value from the array, and remove it.**
     *
     * @param  array  $array
     * @param  string $key
     * @param  mixed  $default
     *
     * @return mixed
     */
    public static function array_pull(&$array, $key, $default = NULL)
    {
        /** @noinspection ReferenceMismatchInspection */
        $value = self::array_get($array, $key, $default);

        self::array_forget($array, $key);

        return $value;
    }

    /**
     * **Retrieves the value of an array element or object property with the given key or property name.**
     *
     * If the key does not exist in the array or object, the default value will be returned instead.
     * The key may be specified in a dot format to retrieve the value of a sub-array or the property
     * of an embedded object. In particular, if the key is `x.y.z`, then the returned value would
     * be `$array['x']['y']['z']` or `$array->x->y->z` (if `$array` is an object). If `$array['x']`
     * or `$array->x` is neither an array nor an object, the default value will be returned.
     * Note that if the array already has an element `x.y.z`, then its value will be returned
     * instead of going through the sub-arrays.
     *
     * Examples:
     *
     *  // working with array
     *  $username = Util::query($_POST, 'username');
     *
     *  // working with object
     *  $username = Util::query($user, 'username');
     *
     *  // working with anonymous function
     *  $fullName = Util::query($user, static function ($user, $defaultValue) {
     *     return $user->firstName . ' ' . $user->lastName;
     *  });
     *
     *  // using dot format to retrieve the property of embedded object
     *  $street = Util::query($users, 'address.street');
     *
     * @param array|mixed $array       array or object to extract value from
     * @param string      $key         key name of the array element, or property name of the object,
     *                                 or an anonymous static function returning the value. The anonymous function
     *                                 signature should be:
     *                                 `function($array, $defaultValue)`.
     * @param mixed       $default     the default value to be returned if the specified array key does not exist.
     *                                 Not used when getting value from an object.
     *
     * @return mixed the value of the element if found, default value otherwise
     */
    public static function array_query($array, $key, $default = NULL)
    {
        if ($key instanceof \Closure) {
            return $key($array, $default);
        }

        if (is_array($array) && array_key_exists($key, $array)) {
            return $array[$key];
        }

        if (($pos = strrpos($key, '.')) !== FALSE) {
            $array = self::array_query($array, substr($key, 0, $pos), $default);
            $key = substr($key, $pos + 1);
        }

        if (is_object($array)) {
            return $array->$key;
        }

        if (is_array($array)) {
            return array_key_exists($key, $array) ? $array[$key] : $default;
        }

        return $default;
    }

    /**
     * **Search in-place for a key value, then replace it.**
     *
     * @param      $array
     * @param      $key
     * @param null $default
     *
     * @return mixed|null
     */
    public static function array_search_and_replace(&$array, $key, $default = NULL)
    {
        # if the value is an associative array, then merge it into the array
        # and return the result.
        if (self::is_assoc($key) and count($key) > 0) {
            // Merge given config settings over any previous ones (if $value is array)
            //$array = self::merge_recursive_replace($array, $key);
            return ($array = self::merge_recursive_replace($array, $key));
        }

        # otherwise, return the normalized value without replace
        /** @noinspection ReferenceMismatchInspection */
        return (strpos($key, '.') !== FALSE)
            ? self::value_from_notation($array, $key)
            : self::array_value($array, $key, $default);

    }

    /**
     * **Set an array item to a given value using "dot" notation.**
     *
     * If no key is given to the method, the entire array will be replaced.
     *
     * @param  array  $array
     * @param  string $key
     * @param  mixed  $value
     *
     * @return array
     */
    public static function array_set(&$array, $key, $value)
    {
        if (NULL === $key) {
            return ($array = $value);
        }

        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if ( ! isset($array[$key]) || ! is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }

    /**
     * **Get a subset of the items from the given array.**
     *
     * @param  array        $array
     * @param  array|string $keys
     *
     * @return array
     */
    public static function array_subset($array, $keys)
    {
        return array_intersect_key($array, array_flip((array) $keys));
    }

    /**
     * **Create a copy of the array parameter with a numeric index.**
     *
     * ie:
     *      ['apple','orange','banana'] becomes [0]=>'apple', [1]=>'orange', [2]=>'banana'.
     *
     * @param $array
     *
     * @return array - result is a new array with added integer index.
     */
    public static function array_to_numeric_index($array)
    {
        $index = 0;
        $work = [];
        foreach ($array as $key => $field) {
            $work[$index++] = $field;
        }

        return $work;
    }

    /**
     * **Copy an array using a callback on each element.**
     *
     * given:   `$keys = ['a'=>1,'b'=>2,'c'=>3,'d'=>4,]`
     *
     * call:    `$result = array_copy_with_callback(
     *              function ($key, $value) {
     *                  static $count = 0;
     *
     *                  return [$key, $value * $count++];
     *
     *              }, $keys);
     *
     *              var_export($result);
     *          }`
     *
     * result:  ['a'=> 0,'b'=> 2,'c'=> 6,'d' => 12,)
     *
     * @param  callable $callback
     * @param  array    $source
     *
     * @return array
     */
    public static function array_transform_with_callback(callable $callback, $source)
    {
        $results = [];

        foreach ($source as $key => $value) {
            list($innerKey, $innerValue) = call_user_func($callback, $key, $value);

            $results[$innerKey] = $innerValue;
        }

        return $results;
    }

    /**
     * **Retrieve an array value - handling callable values if encountered.**
     *
     * @param $array
     * @param $key
     * @param $default
     *
     * @return mixed|null
     */
    public static function array_value($array, $key, $default)
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }
        else {
            return value($default);
        }
    }

    /**
     * **Filter the array using the given callback.**
     *
     * @param  array    $array
     * @param  callable $callback
     *
     * @return array
     */
    public static function array_where($array, callable $callback)
    {
        $filtered = [];

        foreach ($array as $key => $value) {
            if ($callback($key, $value)) {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    /**
     * **Convert a string in the form "<key>:<value," to an associative array.**
     *
     * @param $tuples - the formatted string representation of a key/value array.
     *
     * @return array  - the constructed array
     */
    public static function assoc_from_string($tuples)
    {
        $array = self::array_from_string($tuples, ',');
        $result = [];

        foreach ($array as $tuple) {
            $explode = explode(':', $tuple);

            $key = trim($explode[0]);
            $value = trim($explode[1]);

            $result[$key] = is_numeric($value) ? (int) $value : $value;
        }

        return $result;
    }

    /**
     * **Collapse an array of arrays into a single array.**
     *
     * @param  array $array
     *
     * @return array
     */
    public static function collapse($array)
    {
        $results = [];

        foreach ($array as $values) {
            if (is_object($values) and method_exists($values, 'toArray')) {
                $values = $values->toArray();
            }
            elseif ( ! is_array($values)) {
                continue;
            }

            /** @noinspection SlowArrayOperationsInLoopInspection */
            $results = array_merge($results, $values);
        }

        return $results;
    }

    /**
     * **Expand a dot-notated key/value to an array.**
     * i.e.: key:'this.thing', value:'exists' -> ['this'=>['thing'=>'exists']]
     *
     * @param $key
     * @param $value
     *
     * @return array
     */
    public static function expand_segments($key, $value)
    {
        # extract dot-notation array structures and build the query array
        $params = [];
        static::from_notation($key, $params, $value);

        # first element is the base key
        $key = array_keys($params)[0];

        # the result is the value array (or element)
        $value = array_shift($params);

        return [$key, $value];
    }

    /**
     * **Extracts a value from an array of associated arrays.**
     * ie:
     *    $records = [
     *      'George' => ['age' => 26, 'gender' => 'Male'],
     *      'Lois'   => ['age' => 32, 'gender' => 'Female'],
     *      ];
     *    `array_extract_list('age', $records)` returns `[26,32]`
     *
     * @param array  $array
     *
     * @param string $find_key
     *
     * @return array
     */
    public static function extract_column($array, $find_key)
    {
        $result = [];
        foreach ($array as $element) {
            # replaces non-matched items with NULL as a place-keeper.
            $result[] = isset($element[$find_key]) ?? $element[$find_key];
        }

        return $result;
    }

    /**
     * **Flatten a multi-dimensional array into a single level.**
     *
     * @param  array $array
     *
     * @return array
     */
    public static function flatten($array)
    {
        $return = [];

        array_walk_recursive($array, function ($x) use (&$return) {
            $return[] = $x;
        });

        return $return;
    }

    /**
     * @param string $dot_path
     * @param array  $target_array
     * @param string $target_value
     *
     * @return string
     */
    public static function from_notation($dot_path, &$target_array, $target_value = NULL)
    {
        $result_array = &$target_array;

        foreach (explode('.', $dot_path) as $step) {
            $result_array = &$result_array[$step];
        }

        return $target_value ? $result_array = $target_value : $result_array;
    }

    /**
     * **Insert an key/value pair before a given key in an array.**
     *
     * @param array  $originalKey   - the key into the working array
     * @param array  $originalArray - the working array
     * @param string $insertKey     - the key to insert before the working array[key] element
     * @param string $insertValue   - the value of the array[key] to insert
     *
     * @return array
     */
    public static function insert_before($originalKey, $originalArray, $insertKey, $insertValue)
    {
        $newArray = [];
        $inserted = FALSE;

        /**
         * @var string $key
         * @var        $value
         */
        foreach ($originalArray as $key => $value) {
            if ( ! $inserted && $key === $originalKey) {
                $newArray[$insertKey] = $insertValue;
                $inserted = TRUE;
            }
            $newArray[$key] = $value;
        }

        return $newArray;
    }

    /**
     * **Determines if an array is an associative array.<br>**
     *
     * ie:<br>
     * <ul>
     *    <li>Single dimension array: <pre>array("dog","cat","etc") == FALSE</pre>
     *    <li>Associative array: <pre>array("animal"=>"dog", "place"=>"123 East") == TRUE</pre>
     *
     * @package SupportLoader
     * @module  arrays
     *
     * @param $array - The array to test
     *
     * @return bool   - returns TRUE if an associative array
     */
    public static function is_assoc($array)
    {
        return is_array($array) ? (bool) ! (array_values($array) === $array) : FALSE;
    }

    /**
     * **Determine if the given key exists in the provided array.**
     *
     * @param  \ArrayAccess|array $array
     * @param  string|int         $key
     *
     * @return bool
     */
    public static function key_exists($array, $key)
    {
        if ($array instanceof ArrayAccess) {
            return $array->offsetExists($key);
        }

        return array_key_exists($key, $array);
    }

    /**
     * **Merges any number of arrays of any dimension.**
     *
     * @module  arrays
     *
     * @param null       $key
     * @param null|array $value
     *
     * @return array - Resulting array, once all have been merged
     */
    public static function merge_recursive_replace($key, $value)
    {
        // Holds all the arrays passed
        $params = func_get_args();

        // First array is used as the base, everything else overwrites on it
        $return = array_shift($params);

        // Merge all arrays on the first array
        foreach ($params as $array)
            /** @noinspection SuspiciousLoopInspection */
            foreach ($array as $key => $value)
                if (is_array($value) && (isset($return[$key]) && is_array($return[$key]))) {
                    $return[$key] = static::merge_recursive_replace($return[$key], $value);
                }
                else {
                    $return[$key] = $value;
                }

        return $return;
    }

    /**
     * **Explodes a string on multiple delimiters.**
     *
     * @origin <http://php.net/manual/en/function.explode.php#111307>
     *
     * @param string $string
     * @param array  $delimiters
     * @param bool   $trim
     *
     * @return array
     */
    public static function multi_explode($string, array $delimiters, $trim = FALSE)
    {
        $ready = str_replace($delimiters, $delimiters[0], $string);
        $launch = explode($delimiters[0], $ready);

        return $trim ? str_replace(' ', '', $launch) : $launch;
    }

    /**
     * **Convert to useful array style from HTML Form input style.**
     *
     * Useful for matching up input arrays without having to increment
     * a number in field names<br><br>
     *
     * Input an array like this:<pre>
     *
     * ["name"]  =>  [0] => "Google", [1] => "Yahoo!"
     * ["url"]  =>  [0] => "http://www.google.com", [1] => "http://www.yahoo.com"</pre>
     * And you will get this:<pre>
     * [0]  =>  ["name"] => "Google", ["url"] => "http://www.google.com"
     * [1]  =>  ["name"] => "Yahoo!", ["url"] => "http://www.yahoo.com"</pre>
     *
     * @package SupportLoader
     * @module  arrays
     *
     * @param array $input - a reference to an associative array.
     *
     * @return array - the array flipped.
     */
    public static function pivot_array(array $input)
    {
        $output = [];
        foreach ($input as $key => $val) {
            foreach ($val as $key2 => $val2) {
                $output[$key2][$key] = $val2;
            }
        }

        return $output;
    }

    /**
     * **Retrieve a value from an array using dot-notation.**
     *
     * @param $array
     *
     * @param $key
     *
     * @return mixed
     */
    public static function value_from_notation($array, $key)
    {
        $data_value = $array;
        $valueParts = explode('.', $key);

        foreach ($valueParts as $valuePart) {
            if (isset($data_value[$valuePart])) {
                $data_value = $data_value[$valuePart];
            }
        }

        return $data_value;
    }

    /**
     * **Explode the "value" and "key" arguments passed to "pluck".**
     *
     * @param  string|array      $value
     * @param  string|array|null $key
     *
     * @return array
     */
    protected static function explode_extract_parameters($value, $key)
    {
        $value = is_string($value) ? explode('.', $value) : $value;
        $key = (NULL === $key || is_array($key)) ? $key : explode('.', $key);

        return [$value, $key];
    }

}
