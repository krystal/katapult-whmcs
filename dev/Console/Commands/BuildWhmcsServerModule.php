<?php

namespace Krystal\Katapult\WHMCS\Dev\Console\Commands;

use Composer\Console\Application as Composer;
use GuzzleHttp\Utils;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use \Exception;
use Symfony\Component\Filesystem\Filesystem;

class BuildWhmcsServerModule extends Command
{
	protected static $defaultName = 'build:server-module';

	protected ? string $buildDirectory = '';
	protected ? string $tmpBuildDirectory = '';
	
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
			$this->buildDirectory = realpath(__DIR__ . '/../../../build');

			if (!$this->buildDirectory) {
				throw new Exception('Could not find build directory');
			}

			// Cap it
			$this->buildDirectory = Str::finish($this->buildDirectory, '/');

			// Prep the temp build directory
			$tmpBuildFile = $filesystem->tempnam($this->buildDirectory, 'tmp_katapult_');
			$filesystem->remove($tmpBuildFile);
			$this->tmpBuildDirectory = Str::finish($tmpBuildFile, '/');

			// Create a temp directory to store the module
			$filesystem->mirror($this->buildDirectory . '../', $this->tmpBuildDirectory);

			// Remove files we don't want in the final build
			$this->removeTempFiles(
				'.idea',
				'.git',
				'build',
				'dev',
				'bin',
				'vendor',
				'.gitignore',
			);

			// Fetch the composer.json file
			$composerJson = Utils::jsonDecode(file_get_contents($this->tmpBuildDirectory . 'composer.json'));

			// Modify the composer.json file to exclude Guzzle
			$composerJson->replace = [
				'guzzlehttp/guzzle' => '*'
			];

			// Put it back on disk
			file_put_contents($this->tmpBuildDirectory . 'composer.json', Utils::jsonEncode($composerJson));

			// This updates the lockfile with a version happy without Guzzle.
			$this->runComposer([
				'command' => 'remove',
				'packages' =>['guzzlehttp/guzzle'],
				'--no-install' => true
			]);

			$output->writeln("Installing composer dependencies");

			// Install composer dependencies
			$this->runComposer([
				'command' => 'install',
				'--no-dev' => true
			]);

			// Add .htaccess to vendor dir as WHMCS has it in the doc root
			$filesystem->appendToFile($this->tmpBuildDirectory . 'vendor/.htaccess', 'deny from all');

			// Zip it up!
			$zip = new \ZipArchive();
			$zipFilename = $this->buildDirectory . 'katapult.zip';

			// Can we open it?
			if ($zip->open($zipFilename, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
				throw new \Exception('Cannot open zip file: ' . $zipFilename);
			}

			// Create recursive directory iterator
			$files = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator($this->tmpBuildDirectory),
				\RecursiveIteratorIterator::LEAVES_ONLY
			);

			foreach($files as $name => $file) {
				// Skip directories
				if (!$file->isDir()) {
					// Get real and relative path for current file
					$filePath = $file->getRealPath();
					$relativePath = substr($filePath, strlen($this->tmpBuildDirectory));

					// Add current file to archive
					$zip->addFile($filePath, $relativePath);
				}
			}

			$zip->close();

			return Command::SUCCESS;
		} catch (\Throwable $e) {
			$output->writeln("<error>{$e->getMessage()}</error>");
			return Command::FAILURE;
		} finally {
			// Delete the temp directory
			if($this->tmpBuildDirectory) {
				(new Filesystem())->remove($this->tmpBuildDirectory);
			}
		}
	}

	protected function removeTempFiles(string ...$files)
	{
		(new Filesystem())->remove(collect($files)->map(function($path) {
			return $this->tmpBuildDirectory . $path;
		}));
	}

	protected function removeBuildFiles(string ...$files)
	{
		(new Filesystem())->remove(collect($files)->map(function($path) {
			return $this->buildDirectory . $path;
		}));
	}

	protected function runComposer(array $input)
	{
		$input['--working-dir'] = $this->tmpBuildDirectory;

		$input = new ArrayInput($input);

		$composer = new Composer();
		$composer->setAutoExit(false);

		$composer->run($input);
	}
}

