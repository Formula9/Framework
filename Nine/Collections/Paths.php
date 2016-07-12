<?php namespace Nine\Collections;

/**
 * @package Nine Collections
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

use LogicException;
use Nine\Traits\WithItemArrayAccess;
use Nine\Traits\WithItemImportExport;

/**
 * **Paths provides a simple interface for handling paths in the F9 framework.**
 */
class Paths extends Collection implements PathsInterface
{
    // file and type import and export methods
    use WithItemImportExport;
    // standard set of array access methods
    use WithItemArrayAccess;

    /**
     * Paths constructor.
     *
     * @param array $data
     *
     * @throws \LogicException
     */
    public function __construct(array $data = [])
    {
        // verify/update paths
        foreach ($data as $key => $path)
            /** @noinspection AlterInForeachInspection */
            $data[$key] = $this->normalize_path($path);

        parent::__construct($data);
    }

    /**
     * Adds a new path to the collection.
     *
     * @param string $key
     * @param string $path
     *
     * @return Paths|static
     */
    public function put(string $key, $path) : Paths
    {
        $this->items[$key] = $path = $this->normalize_path($path);

        return $this;
    }

    /**
     * @param array $import
     *
     * @return $this|Paths
     */
    public function merge($import) : Paths
    {
        # set normalize paths
        array_map(
            function ($key, $path) use (&$import) {
                $this->offsetSet($key, $this->normalize_path($path));
            },
            array_keys($import), array_values($import)
        );

        return $this;
    }

    /**
     * @param $path
     *
     * @return string
     */
    private function normalize_path($path) : string
    {
        $path = rtrim(realpath($path), '/') . '/';

        if ( ! file_exists($path)) {
            throw new LogicException("Invalid path: $path.");
        }

        return $path;
    }

}
