<?php

namespace Krystal\Katapult\WHMCS\Dev\Console\Commands;

use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use \Exception;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class BuildWhmcsServerModule extends Command
{
	protected static $defaultName = 'build:server-module';

	protected function configure()
	{
		$this->setDescription('Builds the server module ready to be installed into WHMCS');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		try {
			$filesystem = new Filesystem();

			$output->writeln('<info>Creating server module ZIP file</info>');

			// Find the build dir
			$buildDirectory = realpath(__DIR__ . '/../../../build');

			if (!$buildDirectory) {
				throw new Exception('Could not find build directory');
			}

			// Cap it
			$buildDirectory = Str::finish($buildDirectory, '/');

			// Prep the temp build directory
			$tmpBuildDirectory = $buildDirectory . 'tmp/';
			$filesystem->remove($tmpBuildDirectory);

			// Create a temp directory to store the module
			$filesystem->mirror($buildDirectory . '../', $tmpBuildDirectory);

			// Remove files we don't want in the final build
			$filesystem->remove(collect([
				'.idea',
				'.git',
				'build',
				'dev',
				'bin',
				'vendor',
				'.gitignore',
			])->map(function($path) use($tmpBuildDirectory){
				return $tmpBuildDirectory . $path;
			}));

			// Install composer dependencies
			$process = new Process(['composer install'], $tmpBuildDirectory);
			$process->run();
			$output->write($process->getOutput());

			return Command::SUCCESS;
		} catch (\Throwable $e) {
			$output->writeln("<error>{$e->getMessage()}</error>");
			return Command::FAILURE;
		}
	}
}

