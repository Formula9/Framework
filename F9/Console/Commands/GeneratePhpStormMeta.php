<?php namespace Console\Commands;

use F9\Application\Application;
use Nine\Collections\Attributes;
use Nine\Collections\Config;
use Nine\Collections\GlobalScope;
use Nine\Collections\Paths;
use Nine\Containers\Forge;
use Nine\Database\Connections;
use Nine\Database\Database;
use Nine\Database\DB;
use Nine\Database\NineBase;
use Nine\Views\Blade;
use Nine\Views\BladeView;
use Nine\Views\BladeViewConfigurationInterface;
use Nine\Views\TwigView;
use Nine\Views\TwigViewConfigurationInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */
class GeneratePhpStormMeta extends Command
{
    /**
     * Configure the standard framework properties
     */
    protected function configure()
    {
        $this
            ->setName('generate:phpstorm_meta')
            ->setDescription('Generate the development PhpStorm code-completion file.')
            ->setHelp(<<<EOT
  Generate the development PhpStorm code-completion file.

  Usage:
    <info>formula generate:phpstorm_meta</info>
EOT
            );

        /**
         * Touch common classes to ensure that their dependencies are registered with
         * the Forge. Some of these may already be registered in the boot sequence through
         * `AppFactory::make(...)`.
         */

        //@formatter:off
        $application    = forge()->make(Application::class);
        $attributes     = forge()->make(Attributes::class);
        $blade          = forge()->make(Blade::class);
        $blade_config   = forge()->make(BladeViewConfigurationInterface::class);
        $blade_view     = forge()->make(BladeView::class);
        $config         = forge()->make(Config::class);
        $connections    = forge()->make(Connections::class);
        $database       = forge()->make(Database::class);
        $db             = forge()->make(DB::class);
        $global_scope   = forge()->make(GlobalScope::class);
        $ninebase       = forge()->make(NineBase::class);
        $paths          = forge()->make(Paths::class);
        $twig_config    = forge()->make(TwigViewConfigurationInterface::class);
        $twig_view      = forge()->make(TwigView::class);

        $db_connection  = app('db.connection');
        $db_manager     = app('db');
        //@formatter:on

    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int|null|void
     * @throws \RuntimeException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Forge::makePhpStormMeta();

        $header_style = new OutputFormatterStyle('white', 'default', ['bold']);
        $output->getFormatter()->setStyle('header', $header_style);
        $output->writeln('<header>Generated PhpStorm code-completion file.</header>');
    }

}
