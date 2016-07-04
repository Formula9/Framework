<?php namespace F9\Console\Commands;

use F9\Exceptions\DependencyInstanceNotFound;
use Nine\Containers\Forge;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */
class ClearCacheCommand extends Command
{
    /**
     * Configure the standard framework properties
     *
     * @throws InvalidArgumentException
     */
    protected function configure()
    {
        $this
            ->setName('clear:cache')
            ->setDescription('Clears application caches.')
            ->setHelp(<<<EOT
  Clears all application caches.

EOT
            );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     * @throws DependencyInstanceNotFound
     * @throws \RuntimeException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $header_style = new OutputFormatterStyle('white', 'default', ['bold']);
        $output->getFormatter()->setStyle('header', $header_style);

        $response = shell_exec('rm -Rf ' . CACHE . 'blade/* 2>&1 1> /dev/null');
        $response = shell_exec('rm -Rf ' . CACHE . 'twig/* 2>&1 1> /dev/null');

        Forge::find('logger')->log('info', 'caches were cleared.');

        $output->writeln('<header>Caches cleared.</header>');
    }

}
