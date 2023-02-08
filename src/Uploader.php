<?php

namespace JanwMe\WpSelfHostedPluginThemes;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;

class Uploader {

	protected string $url;
	protected string $username;
	protected string $password;
	protected string $readmeFile;
	protected string $zipFile;
	protected string $slug;
	protected int $parentPageId;
	protected int $readmeFileId;
	protected int $fileZipId;

	/**
	 * @param string      $url        Base url to a WP rest API
	 * @param string      $username   Username that can upload to the rest API
	 * @param string      $password   Preferably an application password
	 * @param string      $readmefile The full path to the readme.txt.
	 * @param string      $zipfile    The full path to the theme of plugin zip-file.
	 * @param string $slug       Slug of the plugin/theme defaults to the basename of the ZIP
	 */
	public function __construct( string $url, string $username, string $password, string $readmefile, string $zipfile, string $slug ) {
		$this->url        = ltrim( $url ) . '/'; // force trailing slash
		$this->username   = $username;
		$this->password   = $password;
		$this->readmeFile = $readmefile;
		$this->zipFile    = $zipfile;
		$this->slug    = $slug;
	}

	public function run() {
		$id = $this->getPage();
		if ( empty( $id ) ) {
			$id = $this->createPage();
		}
		$this->parentPageId = $id;
		// Set meta type.

		// upload txt && set parent
		$this->uploadReadme();

		// upload zip && set parent
		$this->uploadZip();


	}

	/**
	 * Try to get the page for the theme/plugin.
	 *
	 * Try to get the current plugin page.
	 *
	 * @return false|int
	 * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
	 */
	protected function getPage() {

		$response = HttpClient::create()->request(
			'GET',
			$this->url . 'wp-json/wp/v2/pages',
			[
				'query'       => [ 'slug' => $this->slug, 'status' => 'any' ],
				'auth_basic'  => [ $this->username, $this->password ],
				'verify_peer' => ( $this->url !== 'https://repository.lndo.site/' ), // Only for local debugging
			]
		);

		$content = json_decode( $response->getContent() );

		if ( empty( $content ) ) {
			return false; // page does not exist yet.
		}
		if ( empty( $content[0]->id ) ) {
			throw new \Exception( 'Unknown error while getting the page.' );
		}

		return (int) $content[0]->id;
	}

	/**
	 * Create a new page for the theme/plugin.
	 *
	 * @return false|int
	 * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
	 */
	protected function createPage() {
		$body = [
			'title'   => $this->slug,
			'slug'    => $this->slug,
			'content' => '[wp-self-hosted]', // Maybe insert the shortcode.
		];

		$response = HttpClient::create()->request(
			'POST',
			$this->url . 'wp-json/wp/v2/pages',
			[
				'body'        => $body,
				'auth_basic'  => [ $this->username, $this->password ],
				'verify_peer' => ( $this->url !== 'https://repository.lndo.site/' ), // Only for local debugging
			]
		);

		$content = json_decode( $response->getContent() );

		if ( empty( $content ) ) {
			return false; // page does not exist yet.
		}

		if ( empty( $content->id ) ) {
			throw new \Exception( 'Unknown error while getting the page.' );
		}

		return (int) $content->id;
	}

	/**
	 * Upload the readme File.
	 *
	 * @return void
	 * @throws \Exception
	 */
	protected function uploadReadme() {
		$filename           = "{$this->slug}-readme.txt";
		$this->readmeFileId = $this->uploadFile( $this->readmeFile, $this->parentPageId, $filename );
	}

	protected function uploadZip() {
		$filename = basename( $this->zipFile );
		$this->fileZipId = $this->uploadFile( $this->zipFile, $this->parentPageId, $filename );
	}

	/**
	 * Upload a file. Then connect it to the parent.
	 *
	 * @param string      $filePath
	 * @param string      $parentId
	 * @param string|null $filename
	 *
	 * @return int
	 * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
	 * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
	 */
	protected function uploadFile( string $filePath, string $parentId, string $filename = null ): int {
		if ( null === $filename ) {
			$filename = basename( $filePath );
		}

		$response = HttpClient::create()->request( 'POST',
			$this->url . 'wp-json/wp/v2/media',
			[
				'auth_basic'  => [ $this->username, $this->password ],
				'verify_peer' => ( $this->url !== 'https://repository.lndo.site/' ), // Only for local debugging.
				'headers'     => [ 'Content-Disposition' => 'form-data; filename="' . $filename . '"', ],
				'body'        => file_get_contents( $filePath ),
			]
		);

		$content = json_decode( $response->getContent() );

		if ( empty( $content->id ) ) {
			throw new \Exception( 'Unknown error while getting the page.' );
		}

		$mediaId = $content->id;

		// Connect readme to the page.
		$response = HttpClient::create()->request( 'PATCH',
			$this->url . 'wp-json/wp/v2/media/' . $mediaId,
			[
				'auth_basic'  => [ $this->username, $this->password ],
				'verify_peer' => ( $this->url !== 'https://repository.lndo.site/' ), // Only for local debugging.
				'body'        => [
					'post' => $parentId,
				],
			]
		);

		return $mediaId;
	}
}

