<?php

namespace Krystal\Katapult\WHMCS\Dev\Console\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends \Symfony\Component\Console\Command\Command
{
    const LOGO = <<<EOF
 _   __        _                         _  _   
| | / /       | |                       | || |  
| |/ /   __ _ | |_   __ _  _ __   _   _ | || |_ 
|    \  / _` || __| / _` || '_ \ | | | || || __|
| |\  \| (_| || |_ | (_| || |_) || |_| || || |_ 
\_| \_/ \__,_| \__| \__,_|| .__/  \__,_||_| \__|
                          | |                   
                          |_|                   
EOF;

    protected ?InputInterface $input = null;
    protected ?OutputInterface $output = null;

    protected function line()
    {
        $this->output->writeln('');
    }

    protected function logo()
    {
        $this->output->writeln(self::LOGO);
    }

    protected function info(string $line)
    {
        $this->output->writeln("<info>{$line}</info>");
    }

    protected function comment(string $line)
    {
        $this->output->writeln("<comment>{$line}</comment>");
    }

    protected function question(string $line)
    {
        $this->output->writeln("<question>{$line}</question>");
    }

    protected function error(string $line)
    {
        $this->output->writeln("<error>{$line}</error>");
    }
}
