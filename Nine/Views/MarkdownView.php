<?php namespace Nine\Views;

/**
 * @package Noid
 * @version 0.1.0
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

use cebe\markdown\Markdown;

/**
 * **MarkdownView implements a view template renderer for Markdown documents.**
 */
class MarkdownView extends AbstractView
{
    const MARKDOWN_NAMESPACE = "cebe\\markdown\\";

    /** @var Markdown */
    protected $markdown;

    ///** @var array */
    //protected $template_paths = [];

    /**
     * MarkdownView constructor.
     *
     * @param array $settings
     */
    public function __construct($settings = [])
    {
        $settings = $settings ?: config('view.markdown.defaults');
        $this->configure($settings);

        parent::__construct($settings);
    }

    /**
     * @param $templateDir
     *
     * @return mixed
     */
    public function addPath($templateDir)
    {
        $templateDir = realpath($templateDir);

        if ( ! is_dir($templateDir)) {
            throw new \InvalidArgumentException('Markdown: invalid template path.');
        }

        $this->templatePaths[] = realpath($templateDir);

        return $this;
    }

    /**
     * @param array $template_paths
     *
     * @return mixed|void
     * @throws \Twig_Error_Loader
     */
    public function addPaths($template_paths)
    {
        foreach ($template_paths as $template_path)
            $this->addPath($template_path);

        return $this;
    }

    /**
     * get the array of paths from the BladeView view finder.
     *
     * @return array
     */
    public function getViewPaths() : array
    {
        return $this->templatePaths;
    }

    /**
     * @param $template_name
     *
     * @return mixed
     */
    public function hasView($template_name) : bool
    {
        foreach ($this->templatePaths as $template_path)
            if (is_file("$template_path/$template_name")) {
                return TRUE;
            }

        return FALSE;
    }

    /**
     * @param $templateDir
     *
     * @return mixed
     */
    public function prependPath($templateDir)
    {
        array_unshift($this->templatePaths, realpath($templateDir));

        return $this;
    }

    /**
     * Template Service Render Interface
     *
     * @param string $name
     * @param array  $data
     *
     * @return string
     */
    public function render($name, array $data = []) : string
    {
        if ($name === '') {
            throw new \InvalidArgumentException('Markdown template name cannot be empty.');
        }

        $name = (pathinfo($name, PATHINFO_EXTENSION) === '') ? $name . 'md' : $name;
        $data = $this->collectScope($data);

        # search for markdown file
        foreach ($this->templatePaths as $path)
            if (file_exists("$path/$name")) {

                $markdown = file_get_contents("$path/$name");
                $markdown = $this->translate_template_data($markdown, $data);

                return $this->markdown->parse($markdown);
            }

        throw new \InvalidArgumentException('MarkdownTemplateService Error: Template file not found.');
    }

    /**
     * @param string $mark_down
     * @param array  $data
     *
     * @return mixed
     */
    public function translate_template_data($mark_down, array $data = [])
    {
        // @{<include_template_name>}
        $mark_down = preg_replace_callback('$\@\{\s*\w*.md\s*\}$',
            function ($match) use ($data) {

                $template = $match[0];
                $template = str_replace(['@{', '}'], '', $template);

                return $this->include_template($template, $data);
            },
            $mark_down
        );

        foreach ($data as $key => $value) {
            // handle cases where the value is a callable that returns a string
            $value = value($value);

            if ( ! is_array($value) and ! is_object($value)) {
                $mark_down = str_replace(["{{$key}}", "{{ $key }}",], [e($value), e($value),], $mark_down);
            }
        }

        return $mark_down;
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return BladeView|static
     */
    public function with($name, $value) : BladeView
    {
        $this->merge(['name' => $value]);
    }

    /**
     * @param array $configuration
     *
     * @return mixed|void
     */
    private function configure(array $configuration = [])
    {
        (count($configuration) === 0)
            ? $this->templatePaths = config('view.markdown.template_paths')
            : $this->templatePaths = $configuration['template_paths'];

        $class = array_key_exists('type', $configuration) ? $configuration['class'] : 'MarkdownExtra';
        $class = static::MARKDOWN_NAMESPACE . $class;

        $this->markdown = new $class;

        $this->markdown->html5 =
            array_key_exists('html5', $configuration) ? $configuration['html5'] : TRUE;

        $this->markdown->keepListStartNumber =
            array_key_exists('keepListStartNumber', $configuration) ? $configuration['keepListStartNumber'] : TRUE;

        $this->markdown->{'enableNewLines'} =
            ($class = 'GithubMarkdown') and array_key_exists('enableNewLines', $configuration)
            ? $configuration['enableNewLines'] : TRUE;

    }

    /**
     * @param string $template
     * @param array  $data
     *
     * @return string
     */
    private function include_template($template, array $data = [])
    {
        static $cache = [];

        foreach ($this->templatePaths as $template_path)
            if (is_file("$template_path/$template")) {
                if (array_key_exists($template, $cache)) {
                    return $this->translate_template_data($cache[$template], $data);
                }

                $cache[$template] = file_get_contents("$template_path/$template");

                return $this->translate_template_data($cache[$template], $data);
            }

        throw new \InvalidArgumentException("Markdown: template `$template` not found in any path.");
    }
}
