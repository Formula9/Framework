<?php namespace Nine\Collections;

/**
 * **A General Purpose Collection.**
 *
 * @package Nine Collections
 * @version 0.4.2
 */

use Nine\Collections\Interfaces\RetrievableInterface;
use Nine\Collections\Interfaces\StorableInterface;
use Nine\Library\Lib;
use Nine\Traits\WithItemTransforms;

class Collection implements \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable, StorableInterface, RetrievableInterface
{
    use WithItemTransforms;

    /**
     * The items contained in the collection.
     *
     * @var array
     */
    protected $items;

    /** @noinspection ArrayTypeOfParameterByDefaultValueInspection
     *
     * Create a new collection.
     *
     * @param array|Collection|null $items
     */
    public function __construct($items = [])
    {
        $this->items = is_array($items) ? $items : $this->getArrayableItems($items);
    }

    /**
     * Convert the collection to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * Get all of the items in the collection.
     *
     * @return array
     */
    public function all()
    {
        return $this->items;
    }

    /**
     * Collapse the collection of items into a single array.
     *
     * @return static
     */
    public function collapse()
    {
        return new static(static::array_collapse($this->items));
    }

    /**
     * Determine if an item exists in the collection.
     *
     * @param  mixed $key
     * @param  mixed $value
     *
     * @return bool
     */
    public function contains($key, $value = NULL)
    {
        if (func_num_args() === 2) {
            return $this->contains(function ($item) use ($key, $value) {
                return Lib::array_query($item, $key) === $value;
            });
        }

        if ($this->useAsCallable($key)) {
            return NULL !== $this->first($key);
        }

        return in_array($key, $this->items, TRUE);
    }

    /**
     * Count the number of items in the collection.
     *
     * @return int
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * Get the items in the collection that are not present in the given items.
     *
     * @param  mixed $items
     *
     * @return static
     */
    public function diff($items)
    {
        return new static(array_diff($this->items, $this->getArrayableItems($items)));
    }

    /**
     * Get the first item from the collection.
     *
     * @param  callable|null $callback
     * @param  mixed         $default
     *
     * @return mixed
     */
    public function first(callable $callback = NULL, $default = NULL)
    {
        if (NULL === $callback) {
            return count($this->items) > 0 ? reset($this->items) : NULL;
        }

        return Lib::array_first_match($this->items, $callback, $default);
    }

    /**
     * "Paginate" the collection by slicing it into a smaller collection.
     *
     * @param  int $page
     * @param  int $perPage
     *
     * @return static
     */
    public function forPage($page, $perPage)
    {
        return $this->slice(($page - 1) * $perPage, $perPage);
    }

    /**
     * Get an item from the collection by key.
     *
     * @param  string $key
     * @param  mixed $default
     *
     * @return mixed
     */
    public function get(string $key, $default = NULL)
    {
        if ($this->offsetExists($key)) {
            return $this->items[$key];
        }

        return value($default);
    }

    /**
     * Get a CachingIterator instance.
     *
     * @param  int $flags
     *
     * @return \CachingIterator
     */
    public function getCachingIterator($flags = \CachingIterator::CALL_TOSTRING)
    {
        return new \CachingIterator($this->getIterator(), $flags);
    }

    /**
     * Get an iterator for the items.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * Group an associative array by a field or using a callback.
     *
     * @param callable|string $groupBy
     * @param bool            $preserveKeys
     *
     * @return static
     */
    public function groupBy($groupBy, $preserveKeys = FALSE)
    {
        $groupBy = $this->valueRetriever($groupBy);

        $results = [];

        foreach ($this->items as $key => $value) {
            $groupKey = $groupBy($value, $key);

            if ( ! array_key_exists($groupKey, $results)) {
                $results[$groupKey] = new static;
            }

            $results[$groupKey]->{'offsetSet'}($preserveKeys ? $key : NULL, $value);
        }

        return new static($results);
    }

    /**
     * Determine if an item exists in the collection by key.
     *
     * @param  mixed $key
     *
     * @return bool
     */
    public function has($key)
    {
        return $this->offsetExists($key);
    }

    /**
     * Concatenate values of a given key as a string.
     *
     * @param  string $value
     * @param  string $glue
     *
     * @return string
     */
    public function implode($value, $glue = NULL)
    {
        $first = $this->first();

        if (is_array($first) || is_object($first)) {
            return implode($glue, $this->pluck($value)->all());
        }

        return implode($value, $this->items);
    }

    /**
     * Intersect the collection with the given items.
     *
     * @param  mixed $items
     *
     * @return static
     */
    public function intersect($items)
    {
        return new static(array_intersect($this->items, $this->getArrayableItems($items)));
    }

    /**
     * Determine if the collection is empty or not.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return count($this->items) === 0;
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Key an associative array by a field or using a callback.
     *
     * @param  callable|string $keyBy
     *
     * @return static
     */
    public function keyBy($keyBy)
    {
        $keyBy = $this->valueRetriever($keyBy);

        $results = [];

        foreach ($this->items as $item) {
            $results[$keyBy($item)] = $item;
        }

        return new static($results);
    }

    /**
     * Get the keys of the collection items.
     *
     * @return static
     */
    public function keys()
    {
        return new static(array_keys($this->items));
    }

    /**
     * Get the last item from the collection.
     *
     * @param  callable|null $callback
     * @param  mixed         $default
     *
     * @return mixed
     */
    public function last(callable $callback = NULL, $default = NULL)
    {
        if (NULL === $callback) {
            return count($this->items) > 0 ? end($this->items) : value($default);
        }

        return Lib::array_last($this->items, $callback, $default);
    }

    /**
     * Alias for the "pluck" method.
     *
     * @param  string $value
     * @param  string $key
     *
     * @return static
     */
    public function lists($value, $key = NULL)
    {
        return $this->pluck($value, $key);
    }

    /**
     * Run a map over each of the items.
     *
     * @param  callable $callback
     *
     * @return static
     */
    public function map(callable $callback)
    {
        $keys = array_keys($this->items);

        $items = array_map($callback, $this->items, $keys);

        return new static(array_combine($keys, $items));
    }

    /**
     * Get the max value of a given key.
     *
     * @param  string|null $key
     *
     * @return mixed
     */
    public function max($key = NULL)
    {
        return $this->reduce(function ($result, $item) use ($key) {
            $value = Lib::data_get($item, $key);

            return NULL === $result || $value > $result ? $value : $result;
        });
    }

    /**
     * Merge the collection with the given items.
     *
     * @param  mixed $items
     *
     * @return static
     */
    public function merge($items)
    {
        return new static(array_merge($this->items, $this->getArrayableItems($items)));
    }

    /**
     * Get the min value of a given key.
     *
     * @param  string|null $key
     *
     * @return mixed
     */
    public function min($key = NULL)
    {
        return $this->reduce(function ($result, $item) use ($key) {
            $value = Lib::data_get($item, $key);

            return NULL === $result || $value < $result ? $value : $result;
        });
    }

    /**
     * Determine if an item exists at an offset.
     *
     * @param  mixed $key
     *
     * @return bool
     */
    public function offsetExists($key)
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * Get an item at a given offset.
     *
     * @param  mixed $key
     *
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->items[$key];
    }

    /**
     * Set the item at a given offset.
     *
     * @param  mixed $key
     * @param  mixed $value
     *
     * @return void
     */
    public function offsetSet($key, $value)
    {
        if (NULL === $key) {
            $this->items[] = $value;
        }
        else {
            $this->items[$key] = $value;
        }
    }

    /**
     * Unset the item at a given offset.
     *
     * @param  string $key
     *
     * @return void
     */
    public function offsetUnset($key)
    {
        unset($this->items[$key]);
    }

    /**
     * Get an array with the values of a given key.
     *
     * @param  string $value
     * @param  string $key
     *
     * @return static
     */
    public function pluck($value, $key = NULL)
    {
        return new static(Lib::array_get($this->items, $value, $key));
    }

    /**
     * Get and remove the last item from the collection.
     *
     * @return mixed
     */
    public function pop()
    {
        return array_pop($this->items);
    }

    /**
     * Push an item onto the beginning of the collection.
     *
     * @param  mixed $value
     *
     * @return $this
     */
    public function prepend($value)
    {
        array_unshift($this->items, $value);

        return $this;
    }

    /**
     * Pulls an item from the collection.
     *
     * @param  mixed $key
     * @param  mixed $default
     *
     * @return mixed
     */
    public function pull($key, $default = NULL)
    {
        return Lib::array_pull($key, $this->items, $default);
    }

    /**
     * Push an item onto the end of the collection.
     *
     * @param  mixed $value
     *
     * @return $this
     */
    public function push($value)
    {
        $this->offsetSet(NULL, $value);

        return $this;
    }

    /**
     * Put an item in the collection by key.
     *
     * @param  mixed $key
     * @param  mixed $value
     *
     * @return $this
     */
    public function put(string $key, $value)
    {
        $this->offsetSet($key, $value);

        return $this;
    }

    /**
     * Get one or more items randomly from the collection.
     *
     * @param  int $amount
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function random($amount = 1)
    {
        if ($amount > ($count = $this->count())) {
            throw new \InvalidArgumentException("You requested {$amount} items, but there are only {$count} items in the collection");
        }

        $keys = array_rand($this->items, $amount);

        if ($amount === 1) {
            return $this->items[$keys];
        }

        return new static(array_intersect_key($this->items, array_flip($keys)));
    }

    /**
     * Reduce the collection to a single value.
     *
     * @param  callable $callback
     * @param  mixed    $initial
     *
     * @return mixed
     */
    public function reduce(callable $callback, $initial = NULL)
    {
        return array_reduce($this->items, $callback, $initial);
    }

    /**
     * Create a collection of all elements that do not pass a given truth test.
     *
     * @param  callable|mixed $callback
     *
     * @return static
     */
    public function reject($callback)
    {
        if ($this->useAsCallable($callback)) {
            return $this->filter(function ($item) use ($callback) {
                return ! $callback($item);
            });
        }

        return $this->filter(function ($item) use ($callback) {
            return $item !== $callback;
        });
    }

    /**
     * Reverse items order.
     *
     * @return static
     */
    public function reverse()
    {
        return new static(array_reverse($this->items, TRUE));
    }

    /**
     * Search the collection for a given value and return the corresponding key if successful.
     *
     * @param  mixed $value
     * @param  bool  $strict
     *
     * @return mixed
     */
    public function search($value, $strict = FALSE)
    {
        if ( ! $this->useAsCallable($value)) {
            return array_search($value, $this->items, $strict);
        }

        foreach ($this->items as $key => $item) {
            /** @noinspection VariableFunctionsUsageInspection */
            if (call_user_func($value, $item, $key)) {
                return $key;
            }
        }

        return FALSE;
    }

    /**
     * Get and remove the first item from the collection.
     *
     * @return mixed
     */
    public function shift()
    {
        return array_shift($this->items);
    }

    /**
     * Shuffle the items in the collection.
     *
     * @return static
     */
    public function shuffle()
    {
        $items = $this->items;

        shuffle($items);

        return new static($items);
    }

    /**
     * Slice the underlying collection array.
     *
     * @param  int $offset
     * @param  int $length
     *
     * @return static
     */
    public function slice($offset, $length = NULL)
    {
        return new static(array_slice($this->items, $offset, $length, TRUE));
    }

    /**
     * Sort through each item with a callback.
     *
     * @param  callable|null $callback
     *
     * @return static
     */
    public function sort(callable $callback = NULL)
    {
        $items = $this->items;

        $callback
            ? uasort($items, $callback)
            : uasort($items, function ($a, $b) {

            if ($a === $b) {
                return 0;
            }

            return ($a < $b) ? -1 : 1;
        });

        return new static($items);
    }

    /**
     * Sort the collection using the given callback.
     *
     * @param  callable|string $callback
     * @param  int             $options
     * @param  bool            $descending
     *
     * @return static
     */
    public function sortBy($callback, $options = SORT_REGULAR, $descending = FALSE)
    {
        $results = [];

        $callback = $this->valueRetriever($callback);

        foreach ($this->items as $key => $value) {
            $results[$key] = $callback($value, $key);
        }

        $descending ? arsort($results, $options)
            : asort($results, $options);

        /** @noinspection ForeachOnArrayComponentsInspection */
        foreach (array_keys($results) as $key) {
            $results[$key] = $this->items[$key];
        }

        return new static($results);
    }

    /**
     * Sort the collection in descending order using the given callback.
     *
     * @param  callable|string $callback
     * @param  int             $options
     *
     * @return static
     */
    public function sortByDesc($callback, $options = SORT_REGULAR)
    {
        return $this->sortBy($callback, $options, TRUE);
    }

    /**
     * Splice a portion of the underlying collection array.
     *
     * @param  int      $offset
     * @param  int|null $length
     * @param  mixed    $replacement
     *
     * @return static
     */
    public function splice($offset, $length = NULL, array $replacement = [])
    {
        if (func_num_args() === 1) {
            return new static(array_splice($this->items, $offset));
        }

        return new static(array_splice($this->items, $offset, $length, $replacement));
    }

    /**
     * Get the sum of the given values.
     *
     * @param  callable|string|null $callback
     *
     * @return mixed
     */
    public function sum($callback = NULL)
    {
        if (NULL === $callback) {
            return array_sum($this->items);
        }

        $callback = $this->valueRetriever($callback);

        return $this->reduce(
            function ($result, $item) use ($callback) {
                /** @noinspection PhpUnusedLocalVariableInspection */
                return ($result += $callback($item));
            }, 0
        );
    }

    /**
     * Take the first or last {$limit} items.
     *
     * @param  int $limit
     *
     * @return static
     */
    public function take($limit)
    {
        if ($limit < 0) {
            return $this->slice($limit, abs($limit));
        }

        return $this->slice(0, $limit);
    }

    /**
     * Get the collection of items as a plain array.
     *
     * @return array
     */
    public function toArray()
    {
        return array_map(function ($value) {
            return is_object($value) && method_exists($value, 'toArray')
                ? $value->toArray()
                : $value;

        }, $this->items);
    }

    /**
     * Get the collection of items as JSON.
     *
     * @param  int $options
     *
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Transform each item in the collection using a callback.
     *
     * @param  callable $callback
     *
     * @return $this
     */
    public function transform(callable $callback)
    {
        $this->items = $this->map($callback)->all();

        return $this;
    }

    /**
     * Return only unique items from the collection array.
     *
     * @param  string|callable|null $key
     *
     * @return static
     */
    public function unique($key = NULL)
    {
        if (NULL === $key) {
            return new static(array_unique($this->items, SORT_REGULAR));
        }

        $key = $this->valueRetriever($key);

        $exists = [];

        return $this->reject(
            function ($item) use ($key, &$exists) {
                if (in_array($id = $key($item), $exists, TRUE)) {
                    return TRUE;
                }

                $exists[] = $id;

                //@todo - validate that tis works
                return NULL;
            }
        );
    }

    /**
     * Reset the keys on the underlying array.
     *
     * @return static
     */
    public function values()
    {
        return new static(array_values($this->items));
    }

    /**
     * Filter items by the given key value pair.
     *
     * @param  string $key
     * @param  mixed  $value
     * @param  bool   $strict
     *
     * @return static
     */
    public function where($key, $value, $strict = TRUE)
    {
        return $this->filter(function ($item) use ($key, $value, $strict) {
            /** @noinspection TypeUnsafeComparisonInspection */
            return $strict
                ? Lib::data_get($item, $key) === $value
                : Lib::data_get($item, $key) == $value;
        });
    }

    /**
     * Filter items by the given key value pair using loose comparison.
     *
     * @param  string $key
     * @param  mixed  $value
     *
     * @return static
     */
    public function whereLoose($key, $value)
    {
        return $this->where($key, $value, FALSE);
    }

    /**
     * Zip the collection together with one or more arrays.
     *
     * e.g. new Collection([1, 2, 3])->zip([4, 5, 6]);
     *      => [[1, 4], [2, 5], [3, 6]]
     *
     * @param  mixed ...$items
     *
     * @return static
     */
    public function zip($items)
    {
        $arrayableItems = array_map(function ($items) {
            return $this->getArrayableItems($items);
        }, func_get_args());

        $params = array_merge([
            function () {
                return new static(func_get_args());
            },
            $this->items,
        ], $arrayableItems);

        return new static(call_user_func_array('array_map', $params));
    }

    /**
     * Results array of items from Collection or Arrayable.
     *
     * @param  mixed $items
     *
     * @return array
     */
    protected function getArrayableItems($items)
    {
        if ($items instanceof self) {
            return $items->all();
        }

        if (is_object($items) && method_exists($items, 'toArray')) {
            return $items->toArray();
        }

        if (is_object($items) && method_exists($items, 'toJson')) {
            return json_decode($items->toJson(), TRUE);
        }

        return (array) $items;
    }

    /**
     * Determine if the given value is callable, but not a string.
     *
     * @param  mixed $value
     *
     * @return bool
     */
    protected function useAsCallable($value)
    {
        return ! is_string($value) && is_callable($value);
    }

    /**
     * Get a value retrieving callback.
     *
     * @param  string $value
     *
     * @return callable
     */
    protected function valueRetriever($value)
    {
        if ($this->useAsCallable($value)) {
            return $value;
        }

        return function ($item) use ($value) {
            return Lib::data_get($item, $value);
        };
    }
}
