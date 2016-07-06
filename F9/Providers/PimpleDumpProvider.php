<?php namespace F9\Support\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\BootableProviderInterface;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class PimpleDumpProvider implements ServiceProviderInterface, ControllerProviderInterface, BootableProviderInterface
{
    const DIC_PREFIX = 'pimple_dump';

    private $outOfRequestScopeTypes = [];

    private $processed = FALSE;

    /**
     * @param Application $app
     */
    public function boot(Application $app)
    {
        $app->mount('/', $this->connect($app));

        if ($app['debug']) {
            $self = $this;

            $app->after(function (Request $request) use ($app, $self) {
                $self->outOfRequestScopeTypes['request'] = get_class($request);
            });

            $app->finish(function () use ($app, $self) {
                if ( ! $self->processed) {
                    $self->dump($app);
                }
            }, -1);
        }
    }

    public function connect(Application $app)
    {
        $self = $this;
        $controllersFactory = $app['controllers_factory'];
        $routePattern = $app[self::DIC_PREFIX . '.trigger_route_pattern'];
        $responder = function () use ($app, $self) {
            $self->dump($app);
            $count = count($this->getMap($app));

            return "Pimple Container Dumped - $count items added.";
        };

        $controllersFactory->get($routePattern, $responder);

        return $controllersFactory;
    }

    /**
     * @param Container $container
     *
     * @return array
     */
    public function dump(Container $container)
    {
        $map = $this->parseContainer($container);
        sort($map);

        $fileName = $container[self::DIC_PREFIX . '.output_dir'] . '/pimple.json';
        $this->write($map, $fileName);

        $this->processed = TRUE;

        return $map;
    }

    /**
     * @param Container $container
     *
     * @return array
     */
    public function getMap(Container $container)
    {
        $map = $this->parseContainer($container);
        sort($map);

        return $map;
    }

    /**
     * @param Container $app
     */
    public function register(Container $app)
    {
        // Set defaults
        $param = self::DIC_PREFIX . '.output_dir';
        if ( ! isset($app[$param])) {
            // Provide backward compatibility via the old parameter
            //  or set to the default — Composer's parent directory
            $app[$param] = $app['dump.path'] ?? rtrim(ROOT, '/');
            //$app[$param] = isset($app['dump.path']) ? $app['dump.path'] : rtrim(ROOT, '/');
        }

        $param = self::DIC_PREFIX . '.trigger_route_pattern';
        if ( ! isset($app[$param])) {
            $app[$param] = '/_dump';
        }

        $app['pimple_dump_service'] = $this;
    }

    /**
     * Generate a mapping of the container's values
     *
     * @param Container $container
     *
     * @return array
     */
    protected function parseContainer(Container $container)
    {
        $map = [];

        foreach ($container->keys() as $name) {
            if (strpos($name, self::DIC_PREFIX) === 0) {
                continue;
            }

            if ($item = $this->parseItem($container, $name)) {
                $map[] = $item;
            }
        }

        return $map;
    }

    /**
     * Parse the item's type and value
     *
     * @param Container $container
     * @param string    $name
     *
     * @return array|null
     */
    protected function parseItem(Container $container, $name)
    {
        try {
            $element = $container[$name];
        } catch (\Exception $e) {
            if (array_key_exists($name, $this->outOfRequestScopeTypes)) {
                return [
                    'name'  => $name,
                    'type'  => 'class',
                    'value' => $this->outOfRequestScopeTypes[$name],
                ];
            }

            return NULL;
        }

        if (is_object($element)) {
            if ($element instanceof \Closure) {
                $type = 'closure';
                $value = '';
            }
            elseif ($element instanceof Container) {
                $type = 'container';
                $value = $this->parseContainer($element);
            }
            else {
                $type = 'class';
                $value = get_class($element);
            }
        }
        elseif (is_array($element)) {
            $type = 'array';
            $value = '';
        }
        elseif (is_string($element)) {
            $type = 'string';
            $value = $element;
        }
        elseif (is_int($element)) {
            $type = 'int';
            $value = $element;
        }
        elseif (is_float($element)) {
            $type = 'float';
            $value = $element;
        }
        elseif (is_bool($element)) {
            $type = 'bool';
            $value = $element;
        }
        elseif ($element === NULL) {
            $type = 'null';
            $value = '';
        }
        else {
            $type = 'unknown';
            $value = gettype($element);
        }

        return [
            'name'  => $name,
            'type'  => $type,
            'value' => $value,
        ];
    }

    /**
     * Dump mapping to file
     *
     * @param array  $map
     * @param string $fileName
     */
    protected function write($map, $fileName)
    {
        $content = json_encode($map, JSON_PRETTY_PRINT);

        if ( ! file_exists($fileName)) {
            file_put_contents($fileName, $content);

            return;
        }

        $oldContent = file_get_contents($fileName);
        // prevent file lastModified time change
        if ($content !== $oldContent) {
            file_put_contents($fileName, $content);
        }
    }
}
