<?php namespace F9\Console\Commands;

/**
 * @package Nine
 *
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */

use F9\Exceptions\DependencyInstanceNotFound;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearLogsCommand extends Command
{
    /**
     * Configure the standard framework properties
     *
     * @throws InvalidArgumentException
     */
    protected function configure()
    {
        $this
            ->setName('clear:logs')
            ->setDescription('Clear all logs.')
            ->setHelp(<<<EOT
  Clears the log files from the root log folder only.

  Usage:
    <info>formula clear:logs</info>
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

        $response = shell_exec('rm -Rf ' . LOGS . '* 2>&1 1> /dev/null');

        $output->writeln($response);

        app('logger')->log('info', 'logs were cleared.');
        $output->writeln('<header>Logs cleared.</header>');
    }
}
