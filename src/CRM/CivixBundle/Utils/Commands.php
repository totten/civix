<?php
namespace CRM\CivixBundle\Utils;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Helper for running Symfony commands
 */
class Commands {

    /**
     * Create a new process which executes a different commands
     *
     * @param string $cmd
     * @return \Symfony\Component\Process\Process
     */
    public static function createProcess($cmd, $timeout = 300)
    {
        $process = new Process(self::createShellCommand($cmd), null, null, null, $timeout);
        return $process;
    }

    /**
     * Generate the shell statement for invoking of the Symfony commands
     *
     * @param $cmd symfony command
     * @return string a valid shell command
     */
    public static function createShellCommand($cmd)
    {
        $php = escapeshellarg(self::getPhp());
        $console = escapeshellarg($_SERVER['PHP_SELF']);
        return $php.' '.$console.' '.$cmd;
    }

    protected static function getPhp()
    {
        $phpFinder = new PhpExecutableFinder();
        if (!$phpPath = $phpFinder->find()) {
            throw new \RuntimeException('The php executable could not be found, add it to your PATH environment variable and try again');
        }
        return $phpPath;
    }
}
