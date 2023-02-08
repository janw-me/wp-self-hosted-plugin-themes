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
		$this->addOption( 'slug', 's', InputOption::VALUE_REQUIRED,
			'Plugin/them page slug on the target url, defaults to plugin/theme slug.'
		);
		$this->addOption( 'readme-file', 'r', InputOption::VALUE_REQUIRED,
			'Path to the readme.txt file.',
		);
		$this->addOption( 'zip-file', 'z', InputOption::VALUE_REQUIRED,
			'Path to the zip file.',
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
		// The upload details.
		$uploader = new Uploader(
			$input->getArgument( 'target_url' ),
			$input->getArgument( 'username' ),
			$input->getArgument( 'password' ),
			$input->getOption( 'readme-file' ),
			$input->getOption( 'zip-file' ),
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
}
