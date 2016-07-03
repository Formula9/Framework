<?php namespace Nine\Collections;

/**
 * @package Nine Collections
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

use Nine\Traits\WithItemArrayAccess;
use Nine\Traits\WithItemImportExport;

/**
 * **Config provides a central, standardised method of handling
 * configuration files and settings in the F9 framework.**
 *
 * A general purpose configuration class with import/export methods
 * and \ArrayAccess with `dot` notation access methods.
 */
class Config extends Collection implements ConfigInterface
{
    const COMPILED_CONFIG_FILENAME = '_compiled.php_';

    // for YAML and JSON import and export methods
    use WithItemImportExport;

    // for \ArrayAccess methods that support `dot` indexes
    use WithItemArrayAccess;

    /** @var string $base_path The base path to a configuration directory. */
    protected $base_path = '';

    /**
     * Compile the Config contents to a single file in the given path.
     *
     * @param string      $path     Defaults to the path in the CONFIG constant.
     * @param string|NULL $filename Defaults to the filename in the self::COMPILED_CONFIG_FILENAME constant.
     */
    public function compile(string $path = CONFIG, string $filename = self::COMPILED_CONFIG_FILENAME)
    {
        if ( ! $this->has('compiled')) {
            $this['compiled'] = $path . $filename;
        }

        $this->exportPHPFile($path, '*', $filename);

        $this->forget('compiled');
    }

    /**
     * @param array $import
     */
    public function importArray(Array $import)
    {
        array_map(
            function ($key, $value) { $this->set($key, $value); },
            array_keys($import), array_values($import)
        );
    }

    /**
     * @param string $base_path
     * @param string $compiled_filename
     */
    public function importCompiledFile(string $base_path = CONFIG, string $compiled_filename = self::COMPILED_CONFIG_FILENAME)
    {
        $import = include $base_path . $compiled_filename;

        foreach ($import as $key => $item) {
            $this->items[$key] = $item;
        }
    }

    /**
     * @param string $file
     *
     */
    public function importFile($file)
    {
        $this->import_files($file, '.php');
    }

    /**
     * Imports (merges) config files found in the specified directory.
     *
     * @param string $base_path
     * @param string $mask
     *
     * @return Config
     */
    public function importFolder($base_path, $mask = '*.php') : Config
    {
        // determine if the requested folder has been compiled.
        if ($mask === '*.php' and $this->isCompiled()) {
            // yes, so import the compiled file instead.
            $this->importCompiledFile();

            return $this;
        }

        // extract the extension from the mask
        $extension = str_replace('*', '', $mask);

        // import the files
        $this->import_files($this->parse_folder($base_path, $mask), $extension);

        return $this;
    }

    /**
     * Determines if a folder of PHP configuration files has been compiled.
     *
     * @param string $base_path         Defaults to the CONFIG path.
     * @param string $compiled_filename Defaults to the self::COMPILED_CONFIG_FILENAME filename.
     *
     * @return bool
     */
    public function isCompiled(string $base_path = CONFIG, string $compiled_filename = self::COMPILED_CONFIG_FILENAME) : bool
    {
        return file_exists($base_path . $compiled_filename);
    }

    /**
     * @param string $path
     *
     * @return Config
     * @throws \InvalidArgumentException
     */
    public function setBasePath(string $path) : Config
    {
        if (is_dir($path)) {
            $this->base_path = $path;
        }
        else {
            throw new \InvalidArgumentException("Config base path `$path` does not exist.");
        }

        return $this;
    }

    /**
     *
     * @param string $folder
     *
     * @return Config|static
     */
    static public function createFromFolder($folder) : Config
    {
        return (new static)->importFolder($folder);
    }

    /**
     * @param string $json - filename or JSON string
     *
     * @return static
     *
     * @throws \InvalidArgumentException
     */
    static public function createFromJson($json)
    {
        $config = new static;
        $config->importJSON($json);

        return $config;
    }

    /**
     * @param $yaml
     *
     * @return Config|static
     */
    static public function createFromYaml($yaml) : Config
    {
        $config = new static;
        $config->importYAML($yaml);

        return $config;
    }

    /**
     * Register a configuration using the base name of the file.
     *
     * @param        $extension
     * @param        $file_path
     * @param string $key
     */
    private function import_by_extension($extension, $file_path, $key = '')
    {
        $extension = strtolower(str_replace('*', '', $extension));

        if ( ! in_array($extension, ['.json', '.php', '.yaml', '.yml'], TRUE)) {
            throw new \InvalidArgumentException("Invalid import extension: `$extension`");
        }

        # add the base path if necessary
        $file_path = file_exists($file_path) ? $file_path : $this->base_path . "/$file_path";

        # include only if the root key does not exist
        if ( ! $this->offsetExists($key)) {
            switch ($extension) {
                case '.php':

                    if ( ! file_exists($file_path)) {
                        throw new \InvalidArgumentException("Config file $file_path does not exist.");
                    }

                    /** @noinspection UntrustedInclusionInspection */
                    $import = include "$file_path";
                    break;
                case '.yaml':
                case '.yml':
                    $import = $this->importYAML($file_path);
                    break;
                case '.json':
                    $import = $this->importJSON($file_path);
                    break;
                default :
                    $import = NULL;
                    break;
            }

            # only import if the config file returns an array
            if (is_array($import)) {
                $this->set($key, $import);
            }
        }
    }

    /**
     * Import configuration data from a set of files.
     *
     * @param array  $files
     * @param string $file_extension
     */
    private function import_files(array $files, $file_extension = '.php')
    {
        foreach ($files as $config_file) {
            # use the base name as the config key.
            # i.e.: `config/happy.php` -> `happy`
            $config_key = basename($config_file, $file_extension);

            # load
            $this->import_by_extension($file_extension, $config_file, $config_key);
        }
    }

    /**
     * Glob a set of file names from a normalized path.
     *
     * @param string $base_path
     * @param string $file_extension
     *
     * @return array
     */
    private function parse_folder($base_path, $file_extension = '.php') : array
    {
        $base_path = rtrim(realpath($base_path), '/') . '/';

        return glob($base_path . $file_extension);
    }
}
