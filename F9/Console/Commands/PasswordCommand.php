<?php namespace F9\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\User\User;

/**
 * @package Nine
 * @version 0.4.2
 * @author  Greg Truesdell <odd.greg@gmail.com>
 */
class PasswordCommand extends Command
{
    /**
     * Configure the standard framework properties
     *
     * @throws InvalidArgumentException
     */
    protected function configure()
    {
        $this
            ->setName('nine:password')
            ->setDescription('Generate a password compatible with Formula 9.')
            ->addOption('salt', '-s', InputOption::VALUE_OPTIONAL, 'Use the provided salt when generating the password hash.')
            ->addArgument('password', InputArgument::REQUIRED, 'the clear-text password.')
            ->setHelp(<<<EOT
  Generates a encoded password compatible with Formula 9.

  Usage:
    <info>formula nine:password <password></info>
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
        $header_style = new OutputFormatterStyle('white', 'default', ['bold']);
        $output->getFormatter()->setStyle('header', $header_style);

        $salt = '';
        $password = $input->getArgument('password');
        $salt = $input->getOption('salt') ?: '';

        // built-in security user
        $user = new User('admin', 'password', ['ROLE_USER', 'ROLE_ADMIN']);

        /**
         * Find the encoder for a UserInterface instance
         *
         * @var \Symfony\Component\Security\Core\Encoder\BasePasswordEncoder $encoder
         */
        $encoder = app('security.encoder_factory')->getEncoder($user);

        // compute the encoded password for foo
        $password = $encoder->encodePassword($password, $salt);

        //$output->writeln("Using salt '$salt'");
        $output->writeln("<header>Password using salt='$salt':</header> $password");
    }

}
