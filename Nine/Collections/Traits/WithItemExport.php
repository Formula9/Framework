<?php namespace Nine\Traits;

/**
 * This trait exposes data import methods for an $items property.
 *
 * @package Nine Traits
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

use Nine\Exceptions\CollectionExportWriteFailure;
use Nine\Library\Lib;
use Symfony\Component\Yaml\Yaml;

/**
 * WithItemExport expects that an $items property exists. It cannot operate without it.
 *
 * @property array $items Reference to $items property for hinting.
 */
trait WithItemExport
{
    /**
     * Export the entire collection contents to a json string.
     *
     * @param int $options
     *
     * @return string
     */
    public function exportFormattedJSON($options = 0)
    {
        return Lib::encode_readable_json($this->items, $options);
    }

    /**
     * Export the entire collection contents to a json string.
     *
     * @param int $options
     *
     * @return string
     */
    public function exportJSON($options = 0)
    {
        return json_encode($this->{'items'}, $options);
    }

    /**
     * Export a part or the entirety of the collection to a PHP include file.
     *
     * @param string      $path      - the file to write
     * @param string      $key       - the block of data to write
     *                               - (use '*' to write the entire collection)
     * @param string|null $base_name - the optional base filename
     *
     * @throws CollectionExportWriteFailure
     */
    public function exportPHPFile($path, $key, $base_name = NULL)
    {
        $export_structure = $key === '*' ? var_export($this->{'items'}, TRUE) : var_export($this->{'items'}[$key], TRUE);

        //$key = $key !== '*' ?: 'compiled';
        $base_name = $base_name ?: $key === '*' ? 'export.php' : $key . '.php';

        $export_text = "<?php \n return " . $export_structure . ';';
        $export_filename = $path . $base_name;

        if (file_exists($export_filename)) {
            unlink($export_filename);
        }

        if (FALSE === file_put_contents($export_filename, $export_text)) {
            throw new CollectionExportWriteFailure("Failed exporting `$export_filename` - cannot write contents.");
        }
    }

    /**
     * Export the entire collection contents to a yaml string.
     *
     * @param null $label
     * @param int  $inline
     * @param int  $indent
     *
     * @return string
     */
    public function exportYAML($label = NULL, $inline = 4, $indent = 4)
    {
        return $label
            ? Yaml::dump([$label => $this->{'items'}], $inline, $indent)
            : Yaml::dump($this->{'items'}, $inline, $indent);
    }

}
