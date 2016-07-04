<?php namespace Console\Commands;

use Nine\Containers\Forge;
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
            ->setName('generate::phpstorm_meta')
            ->setDescription('Generate the development PhpStorm code-completion file.')
            ->setHelp(<<<EOT
  Generate the development PhpStorm code-completion file.

  Usage:
    <info>formula generate::phpstorm_meta</info>
EOT
            );
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
