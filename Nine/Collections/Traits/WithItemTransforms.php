<?php namespace Nine\Traits;

/**
 * WithItemTransforms expects that an $items property exists. It cannot operate without it.
 *
 * @property array $items Reference to $items property for hinting.
 *
 * @package Nine Traits
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */
trait WithItemTransforms
{
    /**
     * Chunk the underlying collection array.
     *
     * @param  int $size
     *
     * @return $this
     */
    public function chunk($size)
    {
        $chunks = [];

        foreach (array_chunk($this->items, $size, TRUE) as $chunk) {
            $chunks[] = new static($chunk);
        }

        return new static($chunks);
    }

    /**
     * Execute a callback over each item.
     *
     * @param  callable $callback
     *
     * @return $this
     */
    public function each(callable $callback)
    {
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key) === FALSE) {
                break;
            }
        }

        return $this;
    }

    /**
     * Create a new collection consisting of every n-th element.
     *
     * @param  int $step
     * @param  int $offset
     *
     * @return $this
     */
    public function every($step, $offset = 0)
    {
        $new = [];

        $position = 0;

        foreach ($this->items as $key => $item) {
            if ($position % $step === $offset) {
                $new[] = $item;
            }

            $position++;
        }

        return new static($new);
    }

    /**
     * Run a filter over each of the items.
     *
     * @param  callable|null $callback
     *
     * @return $this
     */
    public function filter(callable $callback = NULL)
    {
        if ($callback) {
            return new static(array_filter($this->items, $callback));
        }

        return new static(array_filter($this->items));
    }

    /**
     * Flip the items in the collection.
     *
     * @return $this
     */
    public function flip()
    {
        return new static(array_flip($this->items));
    }

    /**
     * Remove an item from the collection by key.
     *
     * @param  mixed $key
     *
     * @return $this
     */
    public function forget($key)
    {
        $this->{'offsetUnset'}($key);

        return $this;
    }

    /**
     * Collapse an array of arrays into a single array.
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

}
