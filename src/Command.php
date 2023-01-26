<?php

namespace JanwMe\WpSelfHostedPluginThemes;

use Symfony\Component\Console\Command\Command as Symfony_Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends Symfony_Command {
	protected static $defaultName = 'deploy';

	protected function configure() {
		$this->addArgument( 'target_url', InputArgument::REQUIRED,
			'The URL of where the plugin/theme should be uploaded.'
		);
		$this->addArgument( 'username', InputArgument::REQUIRED,
			'The username of the url.'
		);
		$this->addArgument( 'password', InputArgument::REQUIRED,
			'The password of the url.'
		);
		$this->addOption( 'type', 't', InputOption::VALUE_REQUIRED,
			'Type of project to "plugin" or "theme".'
		);
		$this->addOption( 'slug', 's', InputOption::VALUE_OPTIONAL,
			'Plugin/them page slug on the target url, defaults to plugin/theme slug.'
		);
		$this->addOption( 'path', null, InputOption::VALUE_OPTIONAL,
			'Where to find the zip and readme.txt, if empty looks in the current directory',
			getcwd()
		);
	}

	/**
	 * Execute the command.
	 *
	 * @param InputInterface  $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 */
	protected function execute( InputInterface $input, OutputInterface $output ) {
		try {
			$type    = $this->validateType( $input->getOption( 'type' ) );
			$files   = $this->getFiles( $input->getOption( 'path' ) );
			$readme  = $files['readme.txt'];
			$zipfile = $files['file.zip'];
			$version = $this->getVersion( $readme );
			var_dump( $version );
		} catch ( \Exception $exception ) {
			$output->writeln( "<error>{$exception->getMessage()}</error>" );

			return Symfony_Command::FAILURE;
		}

		// The upload details.
		$uploader = new Uploader(
			$input->getArgument( 'target_url' ),
			$input->getArgument( 'username' ),
			$input->getArgument( 'password' ),
			$type,
			$readme,
			$zipfile,
			$version,
			$input->getOption( 'slug' )
		);

		try {
			$uploader->run();
		} catch ( \Exception $exception ) {
			$output->writeln( [
				"<error>An error occured while uploading.</error>",
				"<error>{$exception->getMessage()}</error>",
			] );

			return Symfony_Command::FAILURE;
		}

		return Symfony_Command::SUCCESS;
	}

	/**
	 * Validate the type of project.
	 *
	 * @param string $type plugin or theme are valid values.
	 *
	 * @return string
	 * @throws \Exception
	 */
	protected function validateType( string $type ): string {
		if ( ! in_array( $type, [ 'plugin', 'theme' ], true ) ) {
			throw new \Exception( 'Type has to be "plugin" or "theme", invalid type given: "' . $type . '"' );
		}

		return $type;
	}

	protected function getVersion( string $readmeFile ) {
		preg_match_all( '/Stable tag:\s*([.0-9]*)/mi', file_get_contents( $readmeFile ), $versions, PREG_PATTERN_ORDER );
		if ( ! empty( $versions[1][0] ) ) {
			return $versions[1][0];
		}
	}

	/**
	 * Get the readme.txt and the zip file.
	 *
	 * @param string $path The path to check, can be absolute or relative.
	 *
	 * @return array
	 * @throws \Exception
	 */
	protected function getFiles( string $path ) {
		// Is the directory valid.
		if ( ! is_dir( $path ) ) {
			throw new \Exception( 'Invalid path given: ' . $path );
		}
		// Absolute path with trailing slash

		// Readme.
		$path = realpath( $path ) . DIRECTORY_SEPARATOR;
		if ( ! file_exists( $path . 'readme.txt' ) ) {
			throw new \Exception( 'Cannot find a "readme.txt in given path:' . $path );
		}

		// Zip file
		$zip_files = glob( $path . "*.zip" );
		if ( empty( $zip_files ) ) {
			throw new \Exception( 'Cannot find a zip-file in given path:' . $path );
		}
		if ( count( $zip_files ) > 1 ) {
			throw new \Exception( 'found multiple zip-files in given path:' . $path );
		}

		return [
			'readme.txt' => $path . 'readme.txt',
			'file.zip'   => $zip_files[0],
		];
	}
}
