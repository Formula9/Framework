<?php namespace F9\Console\Commands;

/**
 * Class FibonacciCommand
 */

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FibonacciCommand extends Command
{
    /**
     * Configure the standard framework properties
     */
    protected function configure()
    {
        $start = 0;
        $stop = 100;

        $this->setName('demo:fibonacci')
             ->setDescription('Display the fibonacci numbers between 2 given integer values.')
             ->setDefinition([
                 new InputOption('start', 's', InputOption::VALUE_OPTIONAL, 'Start number of the range of Fibonacci number', $start),
                 new InputOption('stop', 'e', InputOption::VALUE_OPTIONAL, 'stop number of the range of Fibonacci number', $stop),
             ])
             ->setHelp(<<<EOT
  Displays fibonacci numbers between a range of numbers given as parameters.

  Usage:
    <info>short demo:fibonacci -s 2 -e 18 <env></info>

    You can also specify just a number and by default the start number will be 0
    <info>short demo:fibonacci -e 18 <env></info>

    If you don't specify a start and a stop number it will set by default [0,100]
    <info>short demo:fibonacci<env></info>

EOT
             );
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $header_style = new OutputFormatterStyle('white', 'default', ['bold']);
        $output->getFormatter()->setStyle('header', $header_style);

        $start = (int) $input->getOption('start');
        $stop = (int) $input->getOption('stop');

        if (($start >= $stop) || ($start < 0)) {
            throw new \InvalidArgumentException('Stop number should be greater than start number');
        }

        $output->writeln('<header>Fibonacci numbers between ' . $start . ' - ' . $stop . '</header>');

        $xnM2 = 0; // set x(n-2)
        $xnM1 = 1;  // set x(n-1)
        //$xn          = 0; // set x(n)
        $totalFiboNr = 0;
        while ($xnM2 <= $stop) {
            if ($xnM2 >= $start) {
                $output->writeln('<header>' . $xnM2 . '</header>');
                $totalFiboNr++;
            }
            $xn = $xnM1 + $xnM2;
            $xnM2 = $xnM1;
            $xnM1 = $xn;

        }
        $output->writeln('<header>Total of Fibonacci numbers found = ' . $totalFiboNr . ' </header>');
    }
}
