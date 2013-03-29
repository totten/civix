<?php
namespace CRM\CivixBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->addOption('yes', NULL, InputOption::VALUE_NONE, 'Answer yes to any questions');
    }

    protected function confirm(InputInterface $input, OutputInterface $output, $message, $default = true)
    {
        if ($input->getOption('yes')) {
           $output->writeln($message . ($default ? 'Y' : 'N'));
           return $default;
        }
        $dialog = $this->getHelperSet()->get('dialog');
        return $dialog->askConfirmation($output, $message, $default);
    }
}