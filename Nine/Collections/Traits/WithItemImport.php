<?php namespace Nine\Traits;

/**
 * @package Nine Traits
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

use Nine\Library\Lib;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * WithItemImport expects that an $items property exists. It cannot operate without it.
 *
 * @property array $items Reference to $items property for hinting.
 */
trait WithItemImport
{
    /**
     * Import (merge) values from a json file into the collection by key.
     *
     * @param $json_string
     *
     * @param $key
     *
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function importJSON($json_string, $key = '')
    {
        $json_string = $this->get_value($json_string);
        $import = json_decode($json_string, TRUE);

        // an empty key signifies direct import
        return $this->import_value($import, $key);
    }

    /**
     * Import (merge) values from a yaml file.
     *
     * @param string $yaml_string
     * @param string $key
     *
     * @return array
     * @throws \InvalidArgumentException
     *
     * @throws ParseException
     */
    public function importYAML($yaml_string, $key = '')
    {
        $yaml_string = $this->get_value($yaml_string);
        $import = YAML::parse($yaml_string);

        return $this->import_value($import, $key);
    }

    /**
     * Gets the contents of a file if $value is a valid filename,
     * otherwise simply returns the value.
     *
     * @param $value
     *
     * @return string
     */
    protected function get_value($value)
    {
        // load the source file from the OS if a valid filename is passed.
        if (file_exists($value)) {
            $value = file_get_contents($value);
        }

        return $value;
    }

    /**
     * Store an imported value.
     *
     * @param $value
     * @param $key
     *
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function import_value($value, $key = '')
    {
        // an empty key signifies direct import
        return $key === ''
            ? $this->merge_value($value)
            : $this->items[$key] = $value;
    }

    /**
     * Import a key-less imported value.
     *
     * @param $value
     *
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function merge_value($value)
    {
        if (Lib::is_assoc($value)) {
            return $this->items = array_merge($this->items, $value);
        }
        else {
            throw new \InvalidArgumentException('Import failed due to malformed source or missing key.');
        }
    }

}
