<?php
/**
 * PHP 7.3 or later
 *
 * @package    KALEIDPIXEL
 * @author     KAZUKI Otsuhata
 * @copyright  2023 (C) Kaleid Pixel
 * @license    MIT License
 * @version    1.1.0
 **/

namespace kaleidpixel;

class JPIPAllow {
	/**
	 * @var GeoIPAllow
	 * @since 1.1.0
	 */
	protected $geoIpAllow = null;

	public function __construct( array $setting = [] ) {
		$setting['country'] = 'JP';

		if ( empty( $setting['output_path'] ) ) {
			$setting['output_path'] = dirname( __DIR__ ) . DIRECTORY_SEPARATOR . '.htaccess';
		}

		$this->geoIpAllow = new GeoIPAllow( $setting );
	}

	/**
	 * @param bool $echo
	 * @param bool $force
	 *
	 * @return void
	 * @since 1.1.0
	 */
	public function read( bool $echo = false, bool $force = false ): void {
		$this->geoIpAllow->read( $echo, $force );
	}
}
