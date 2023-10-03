<?php
/**
 * PHP 7.3 or later
 *
 * @package    KALEIDPIXEL
 * @author     KAZUKI Otsuhata
 * @copyright  2023 (C) Kaleid Pixel
 * @license    MIT License
 * @version    0.0.1
 **/

namespace kaleidpixel;

use DateTime;
use Exception;
use finfo;

class JPIPAllow {
	const IP_LIST_ENDPOINTS = [
		'google'                         => 'https://www.gstatic.com/ipranges/goog.json',
		'googlebot'                      => 'https://developers.google.com/static/search/apis/ipranges/googlebot.json',
		'google-special-crawlers'        => 'https://developers.google.com/static/search/apis/ipranges/special-crawlers.json',
		'google-user-triggered-fetchers' => 'https://developers.google.com/static/search/apis/ipranges/user-triggered-fetchers.json',
		'apnic'                          => 'https://ftp.apnic.net/stats/apnic/delegated-apnic-latest',
	];

	/**
	 * @var string
	 */
	protected $now = '';

	/**
	 * @var string
	 */
	protected $server = '';

	/**
	 * @var string
	 */
	protected $output_path = '';

	/**
	 * @var string
	 */
	protected $add_before_str = '';

	/**
	 * @var string
	 */
	protected $add_after_str = '';

	public function __construct( array $setting = [] ) {
		$this->now = date( 'Y-m-d' );

		if ( !empty( $setting['server'] ) ) {
			$this->server = (string) $setting['server'];
		} else {
			$this->server = 'apache';
		}

		if ( !empty( $setting['output_path'] ) ) {
			$this->output_path = $setting['output_path'];
		} else {
			$this->output_path = dirname( __DIR__ ) . DIRECTORY_SEPARATOR . '.htaccess';
		}

		if ( !empty( $setting['add_before_str'] ) ) {
			$this->add_before_str = $setting['add_before_str'] . "\n\n";
		}

		if ( !empty( $setting['add_after_str'] ) ) {
			$this->add_after_str = $setting['add_after_str'] . "\n\n";
		}
	}

	/**
	 * Wrapper method for the constant IP_LIST_ENDPOINTS.
	 *
	 * @return string[]
	 */
	public static function ipListEndPoints(): array {
		return self::IP_LIST_ENDPOINTS;
	}

	/**
	 * Calculate CIDR.
	 *
	 * @param int $range
	 *
	 * @return int
	 */
	public static function getCIDRRangeIPv4( int $range = 0 ): int {
		return 32 - log( $range, 2 );
	}

	/**
	 * Check if the type of interface between the web server and PHP is CLI.
	 * @see https://stackoverflow.com/questions/933367/php-how-to-best-determine-if-the-current-invocation-is-from-cli-or-web-server
	 *
	 * @return bool
	 */
	public static function is_cli(): bool {
		if ( defined( 'STDIN' ) ) {
			return true;
		}

		if ( PHP_SAPI === 'cli' ) {
			return true;
		}

		if ( stristr( PHP_SAPI, 'cgi' ) && getenv( 'TERM' ) ) {
			return true;
		}

		if ( array_key_exists( 'SHELL', $_ENV ) ) {
			return true;
		}

		if ( empty( $_SERVER['REMOTE_ADDR'] ) && !isset( $_SERVER['HTTP_USER_AGENT'] ) && count( $_SERVER['argv'] ) > 0 ) {
			return true;
		}

		if ( !array_key_exists( 'REQUEST_METHOD', $_SERVER ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Retrieve the HTTP code and content of the specified URL.
	 *
	 * @param string $url
	 * @param array  $header
	 * @param string $method
	 * @param array  $data
	 *
	 * @return array
	 */
	public static function curl_get_content( string $url = '', array $header = [], string $method = 'GET', array $data = [] ): array {
		$result = array();
		$url    = strip_tags( str_replace( array( '"', "'", '`', '´', '¨' ), '', trim( $url ) ) );
		$url    = filter_var( $url, FILTER_SANITIZE_URL );

		if ( !empty( $url ) ) {
			$ch = curl_init();

			curl_setopt( $ch, CURLOPT_URL, $url );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
			curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
			curl_setopt( $ch, CURLOPT_MAXREDIRS, 10 );
			curl_setopt( $ch, CURLOPT_FORBID_REUSE, true );
			curl_setopt( $ch, CURLOPT_FRESH_CONNECT, true );
			curl_setopt( $ch, CURLOPT_HEADER, false );

			if ( mb_strtoupper( $method ) === 'POST' ) {
				curl_setopt( $ch, CURLOPT_POST, true );
				curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $data ) );
			}

			if ( is_array( $header ) && !empty( $header ) ) {
				curl_setopt( $ch, CURLOPT_HTTPHEADER, $header );
			}

			$result['body'] = curl_exec( $ch );
			$result['url']  = curl_getinfo( $ch, CURLINFO_EFFECTIVE_URL );

			if ( defined( 'CURLINFO_HTTP_CODE' ) ) {
				$result['http_code'] = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
			} else {
				$result['http_code'] = (int) curl_getinfo( $ch, CURLINFO_RESPONSE_CODE );
			}

			curl_close( $ch );
		}

		return $result;
	}

	/**
	 * Output the header in the web browser to download the file and initiate the file download.
	 * @see https://qiita.com/fallout/items/3682e529d189693109eb
	 *
	 * @param string      $file_path
	 * @param string|null $mime_type
	 *
	 * @return void
	 */
	public static function download( string $file_path = '', string $mime_type = null ): void {
		if ( !is_readable( $file_path ) ) {
			die( $file_path );
		}

		$mime_type = ( isset( $mime_type ) ) ? $mime_type : ( new finfo( FILEINFO_MIME_TYPE ) )->file( $file_path );

		if ( !preg_match( '/\A\S+?\/\S+/', $mime_type ) ) {
			$mime_type = 'application/octet-stream';
		}

		header( 'Content-Type: ' . $mime_type );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Content-Length: ' . filesize( $file_path ) );
		header( 'Content-Disposition: attachment; filename="' . basename( $file_path ) . '"' );
		header( 'Connection: close' );

		while ( ob_get_level() ) {
			ob_end_clean();
		}

		readfile( $file_path );
		exit;
	}

	/**
	 * Output.
	 *
	 * @param bool $echo
	 * @param bool $force
	 *
	 * @return void
	 */
	public function output( bool $echo = false, bool $force = false ) {
		$contents = $this->create( $force );

		if ( !empty( $contents['body'] ) && $echo === true && $this->is_cli() === false ) {
			print_r( "<pre>${contents['body']}</pre>" );
		} elseif ( !empty( $contents['body'] ) && $this->is_cli() === true ) {
			print_r( 'Output path: ' . str_replace( 'file://', '', $contents['url'] ) );
		} elseif ( empty( $contents['body'] ) ) {
			print_r( 'File is not contents.' );
		} elseif ( $echo === false && $this->is_cli() === false ) {
			$this->download( $contents['url'] );
		}
	}

	/**
	 * Create file.
	 *
	 * @param bool $force
	 *
	 * @return array
	 */
	protected function create( bool $force = false ): array {
		$create = false;

		if ( file_exists( $this->output_path ) ) {
			$file_time = null;

			try {
				$file_time = new DateTime( date( 'Y-m-d', filemtime( $this->output_path ) ) );
			} catch ( Exception $e ) {
			}

			if ( !is_null( $file_time ) ) {
				try {
					$now  = new DateTime( $this->now );
					$diff = $now->diff( $file_time );

					if ( $diff->days > 0 ) {
						$create = true;
					}
				} catch ( Exception $e ) {
				}
			}

			unset( $file_time, $now, $diff );
		} else {
			$create = true;
		}

		if ( $create === true || $force === true ) {
			$str = $this->getIpAllowList();

			unlink( $this->output_path );
			file_put_contents( $this->output_path, $str, FILE_APPEND | LOCK_EX );
		}

		return $this->curl_get_content( "file:///$this->output_path" );
	}

	/**
	 * Get IP Allow List
	 *
	 * @return string
	 */
	protected function getIpAllowList(): string {
		if ( mb_strtolower( $this->server ) !== 'nginx' ) {
			$result = <<<EOL
######################################################
# Restrict access to IP addresses within Japan only. #
# add $this->now                                     #
######################################################
SetEnvIf User-Agent "msnbot" allowbot
SetEnvIf User-Agent "bingbot" allowbot

Order Deny,Allow
Deny from All

## Allow bot
Allow from env=allowbot

## Private IP Address
Allow from localhost
Allow from 192.168.0.0/16

EOL;
		} else {
			// todo: Don't know how to write it in Nginx, so I'm holding off.
			$result = '';
		}

		$result .= "\n" . $this->add_before_str;
		$result .= $this->getGoogleIpAllowList();
		$result .= $this->getGooglebotIpAllowList();
		$result .= $this->getGoogleSpecialCrawlerIpAllowList();
		$result .= $this->getGoogleUserTriggeredFetchersIpAllowList();
		$result .= $this->getJpIpAllow();
		$result .= $this->add_after_str;

		if ( mb_strtolower( $this->server ) === 'nginx' ) {
			$result .= 'deny all;';
		}

		return $result;
	}

	/**
	 * Get Google IP Allow List.
	 *
	 * @return string
	 */
	protected function getGoogleIpAllowList(): string {
		return $this->addGoogleIpAllowList(
			$this->ipListEndPoints()['google'],
			[
				'############################',
				'# Google IP Address Ranges',
				"# {$this->ipListEndPoints()['google']}",
				'############################',
			]
		);
	}

	/**
	 * Get Googlebot IP Allow List.
	 *
	 * @return string
	 */
	protected function getGooglebotIpAllowList(): string {
		return $this->addGoogleIpAllowList(
			$this->ipListEndPoints()['googlebot'],
			[
				'############################',
				'# Googlebot IP Address Ranges',
				"# {$this->ipListEndPoints()['googlebot']}",
				'############################',
			]
		);
	}

	/**
	 * Get Google special crawler IP Allow List.
	 *
	 * @return string
	 */
	protected function getGoogleSpecialCrawlerIpAllowList(): string {
		return $this->addGoogleIpAllowList(
			$this->ipListEndPoints()['google-special-crawlers'],
			[
				'############################',
				'# Google special crawler IP Address Ranges',
				"# {$this->ipListEndPoints()['google-special-crawlers']}",
				'############################',
			]
		);
	}

	/**
	 * Get Google user triggered fetchers IP Allow List.
	 *
	 * @return string
	 */
	protected function getGoogleUserTriggeredFetchersIpAllowList(): string {
		return $this->addGoogleIpAllowList(
			$this->ipListEndPoints()['google-user-triggered-fetchers'],
			[
				'############################',
				'# Google user triggered fetchers IP Address Ranges',
				"# {$this->ipListEndPoints()['google-user-triggered-fetchers']}",
				'############################',
			]
		);
	}

	/**
	 * Add Googlebot IP Allow List.
	 *
	 * @param string $endpoint
	 * @param array  $header
	 *
	 * @return string
	 */
	protected function addGoogleIpAllowList( string $endpoint, array $header ): string {
		$contents = $this->curl_get_content( $endpoint );
		$result   = $header;

		if ( $contents['http_code'] === 200 ) {
			$lines = json_decode( $contents['body'] );
		} else {
			return implode( PHP_EOL, $result );
		}

		unset( $contents );

		foreach ( $lines->prefixes as $key => $prefixe ) {
			if ( isset( $prefixe->ipv4Prefix ) ) {
				switch ( mb_strtolower( $this->server ) ) {
					default:
					case 'apache':
						$result[] = "Allow from $prefixe->ipv4Prefix";
						break;
					case 'nginx':
						$result[] = "allow $prefixe->ipv4Prefix;";
						break;
				}
			}

			unset( $lines->prefixes[$key], $parts );
		}

		array_push( $result, '', '' );

		return implode( PHP_EOL, $result );
	}

	/**
	 * Get Japan IP Allow List.
	 *
	 * @return string
	 */
	protected function getJpIpAllow(): string {
		$contents = $this->curl_get_content( $this->ipListEndPoints()['apnic'] );
		$result   = [
			'############################',
			'# Japan IP Address Ranges',
			"# {$this->ipListEndPoints()['apnic']}",
			'############################',
		];

		if ( $contents['http_code'] === 200 ) {
			$lines = explode( "\n", $contents['body'] );
		} else {
			return implode( PHP_EOL, $result );
		}

		unset( $contents );

		foreach ( $lines as $key => $line ) {
			$parts = explode( '|', $line );

			if ( count( $parts ) > 4 && $parts[1] == 'JP' && $parts[2] == 'ipv4' ) {
				$cidr = $this->getCIDRRangeIPv4( $parts[4] );

				switch ( mb_strtolower( $this->server ) ) {
					default:
					case 'apache':
						$result[] = "Allow from ${parts[3]}/$cidr";
						break;
					case 'nginx':
						$result[] = "allow ${parts[3]}/$cidr;";
						break;
				}

				unset( $cidr );
			}

			unset( $lines[$key], $parts );
		}

		array_push( $result, '', '' );

		return implode( PHP_EOL, $result );
	}
}
