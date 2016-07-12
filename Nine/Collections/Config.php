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

    /** @var string $basePath The base path to a configuration directory. */
    protected $basePath = '';

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
            function ($key, $value) { $this->put($key, $value); },
            array_keys($import), array_values($import)
        );
    }

    /**
     * @param string $basePath
     * @param string $compiledFilename
     */
    public function importCompiledFile(string $basePath = CONFIG, string $compiledFilename = self::COMPILED_CONFIG_FILENAME)
    {
        $import = include $basePath . $compiledFilename;

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
        $this->importFiles($file, '.php');
    }

    /**
     * Imports (merges) config files found in the specified directory.
     *
     * @param string $basePath
     * @param string $mask
     *
     * @return Config
     */
    public function importFolder($basePath, $mask = '*.php') : Config
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
        $this->importFiles($this->parseFolder($basePath, $mask), $extension);

        return $this;
    }

    /**
     * Determines if a folder of PHP configuration files has been compiled.
     *
     * @param string $basePath         Defaults to the CONFIG path.
     * @param string $compiledFilename Defaults to the self::COMPILED_CONFIG_FILENAME filename.
     *
     * @return bool
     */
    public function isCompiled(string $basePath = CONFIG, string $compiledFilename = self::COMPILED_CONFIG_FILENAME) : bool
    {
        return file_exists($basePath . $compiledFilename);
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
            $this->basePath = $path;
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
     * @param        $filePath
     * @param string $key
     */
    private function importByExtension($extension, $filePath, $key = '')
    {
        $extension = strtolower(str_replace('*', '', $extension));

        if ( ! in_array($extension, ['.json', '.php', '.yaml', '.yml'], TRUE)) {
            throw new \InvalidArgumentException("Invalid import extension: `$extension`");
        }

        # add the base path if necessary
        $filePath = file_exists($filePath) ? $filePath : $this->basePath . "/$filePath";

        # include only if the root key does not exist
        if ( ! $this->offsetExists($key)) {
            switch ($extension) {
                case '.php':

                    if ( ! file_exists($filePath)) {
                        throw new \InvalidArgumentException("Config file $filePath does not exist.");
                    }

                    /** @noinspection UntrustedInclusionInspection */
                    $import = include "$filePath";
                    break;
                case '.yaml':
                case '.yml':
                    $import = $this->importYAML($filePath);
                    break;
                case '.json':
                    $import = $this->importJSON($filePath);
                    break;
                default :
                    $import = NULL;
                    break;
            }

            # only import if the config file returns an array
            if (is_array($import)) {
                $this->put($key, $import);
            }
        }
    }

    /**
     * Import configuration data from a set of files.
     *
     * @param array  $files
     * @param string $fileExtension
     */
    private function importFiles(array $files, $fileExtension = '.php')
    {
        foreach ($files as $config_file) {
            # use the base name as the config key.
            # i.e.: `config/happy.php` -> `happy`
            $config_key = basename($config_file, $fileExtension);

            # load
            $this->importByExtension($fileExtension, $config_file, $config_key);
        }
    }

    /**
     * Glob a set of file names from a normalized path.
     *
     * @param string $basePath
     * @param string $fileExtension
     *
     * @return array
     */
    private function parseFolder($basePath, $fileExtension = '.php') : array
    {
        $basePath = rtrim(realpath($basePath), '/') . '/';

        return glob($basePath . $fileExtension);
    }
}
