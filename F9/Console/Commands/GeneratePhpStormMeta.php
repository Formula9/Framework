<?php namespace Console\Commands;

use F9\Application\AppFactory;
use F9\Application\Application;
use Nine\Collections\Attributes;
use Nine\Collections\Config;
use Nine\Collections\GlobalScope;
use Nine\Collections\Paths;
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

        ////@formatter:off
        //forge()->make(Application::class);
        //forge()->make(Attributes::class);
        //forge()->make(Blade::class);
        //forge()->make(BladeViewConfigurationInterface::class);
        //forge()->make(BladeView::class);
        //forge()->make(Config::class);
        //forge()->make(Connections::class);
        //forge()->make(Database::class);
        //forge()->make(DB::class);
        //forge()->make(GlobalScope::class);
        //forge()->make(NineBase::class);
        //forge()->make(Paths::class);
        //forge()->make(TwigViewConfigurationInterface::class);
        //forge()->make(TwigView::class);
        //
        //app('db.connection');
        //app('db');
        ////@formatter:on

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
        $app = AppFactory::make(forge('paths')->toArray());

        //@formatter:off
        forge()->make(Application::class);
        forge()->make(Attributes::class);
        forge()->make(Blade::class);
        forge()->make(BladeViewConfigurationInterface::class);
        forge()->make(BladeView::class);
        forge()->make(Config::class);
        forge()->make(Connections::class);
        forge()->make(Database::class);
        forge()->make(DB::class);
        forge()->make(GlobalScope::class);
        forge()->make(NineBase::class);
        forge()->make(Paths::class);
        forge()->make(TwigViewConfigurationInterface::class);
        forge()->make(TwigView::class);

        app('db.connection');
        app('db');
        //@formatter:on

        forge()->makePhpStormMeta();

        $headerStyle = new OutputFormatterStyle('white', 'default', ['bold']);
        $output->getFormatter()->setStyle('header', $headerStyle);
        $output->writeln('<header>Generated PhpStorm code-completion file.</header>');
    }

}
