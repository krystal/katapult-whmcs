<?php

namespace Krystal\Katapult\WHMCS\Dev\Console\Commands;

use Composer\Console\Application as Composer;
use GuzzleHttp\Utils;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use \Exception;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class BuildWhmcsServerModule
 * @package Krystal\Katapult\WHMCS\Dev\Console\Commands
 *
 * Quick implementation to package up the module for distribution.
 * Copies the module into a temp dir, removes unnecessary files, adds some files (ie, .htaccess in vendor), installs Composer dependencies without Guzzle and then ZIPs it all up into katapult.zip
 * If the need ever arises for pre-compiled JS/CSS then this could also handle doing that, using something like Laravel mix.
 */
class BuildWhmcsServerModule extends Command
{
	protected static $defaultName = 'build:server-module';

	protected ? string $buildDirectory = null;
	protected ? string $tmpBuildDirectory = null;

	const ZIP_FILENAME = 'katapult.zip';
	
	protected function configure()
	{
		$this->setDescription('Builds the server module ready to be installed into WHMCS');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		try {
			$this->input = $input;
			$this->output = $output;

			$this->logo();
			$this->line();
			$this->info('Starting Katapult WHMCS server module build process');

			$filesystem = new Filesystem();

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
			$this->info("Building module from temp build directory: {$this->tmpBuildDirectory}");

			// Create a temp directory to store the module
			$filesystem->mirror($this->buildDirectory . '../', $this->tmpBuildDirectory);
			$this->info("Copied module over to build directory");

			// Remove files we don't want in the final build
			$this->removeTempFiles(
				'.idea',
				'.git',
				'.github',
				'build',
				'dev',
				'bin',
				'vendor',
				'.gitignore',
			);
/*
			// Fetch the composer.json file
			$this->info("Modifying composer.json to satisfy WHMCS issue with Guzzle being pre-installed");
			$composerJson = Utils::jsonDecode(file_get_contents($this->tmpBuildDirectory . 'composer.json'));

			// Modify the composer.json file to exclude Guzzle
			$composerJson->replace = [
				'guzzlehttp/guzzle' => '*'
			];

			// Put it back on disk
			file_put_contents($this->tmpBuildDirectory . 'composer.json', Utils::jsonEncode($composerJson));*/
/*
			// This updates the lockfile with a version happy without Guzzle.
			$this->info("Removing Guzzle as dependency to force removal from composer.lock");
			$this->runComposer([
				'command' => 'remove',
				'packages' =>['guzzlehttp/guzzle'],
				'--no-install' => true
			]);*/

			// Install composer dependencies
			$this->info("Installing composer dependencies");
			$this->runComposer([
				'command' => 'install',
				'--no-dev' => true
			]);

			// Remove unnecessary composer files.
			// They can get them from the repo, they don't need to be public in WHMCS leaking version information
			$this->removeTempFiles(
				'composer.lock',
				'composer.json',
			);

			// Add .htaccess to vendor dir as WHMCS has it in the doc root
			$this->info("Adding .htaccess deny file to vendor directory");
			$filesystem->appendToFile($this->tmpBuildDirectory . 'vendor/.htaccess', 'deny from all');

			// Zip it up!
			$this->info("Starting ZIP archive");
			$zip = new \ZipArchive();
			$zipFilename = $this->buildDirectory . self::ZIP_FILENAME;
			$this->info("Removing final ZIP file if it already exists");
			$this->removeBuildFiles(self::ZIP_FILENAME);

			// Can we open it?
			if ($zip->open($zipFilename, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
				throw new \Exception('Cannot open zip file: ' . $zipFilename);
			}

			// Create recursive directory iterator
			$files = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator($this->tmpBuildDirectory),
				\RecursiveIteratorIterator::LEAVES_ONLY
			);

			// Loop the files and build the ZIP
			$this->info("Looping files and adding them to the ZIP");
			$filesAdded = 0;
			foreach($files as $name => $file) {
				// Skip directories
				if (!$file->isDir()) {
					// Get real and relative path for current file
					$filePath = $file->getRealPath();
					$relativePath = substr($filePath, strlen($this->tmpBuildDirectory));

					// Is it a .DS_Store from a soon to be Linux user? :troll:
					if (Str::endsWith($filePath, '.DS_Store')) {
						continue;
					}

					// Add current file to archive
					$zip->addFile($filePath, $relativePath);
					$filesAdded++;
				}
			}

			// Close the ZIP
			$this->info("Files added: " . number_format($filesAdded));
			$this->info("Closing ZIP file");
			$zip->close();

			// All done
			$this->comment("Success! The ZIP file has been created successfully:");
			$this->question($zipFilename);

			return Command::SUCCESS;
		} catch (\Throwable $e) {
			$this->error($e->getMessage());
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
		$this->info("Removing temp build files:");

		foreach($files as $file) {
			$this->comment($file);
		}

		(new Filesystem())->remove(collect($files)->map(function($path) {
			return $this->tmpBuildDirectory . $path;
		}));

		$this->info("Finished removing files");
	}

	protected function removeBuildFiles(string ...$files)
	{
		$this->info("Removing build files:");

		foreach($files as $file) {
			$this->comment($file);
		}

		(new Filesystem())->remove(collect($files)->map(function($path) {
			return $this->buildDirectory . $path;
		}));

		$this->info("Finished removing files");
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
