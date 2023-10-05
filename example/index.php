<?php
/**
 * This is a sample source, so it directly requires files within the src directory as well,
 * but in reality, you would only require the autoload.php from the vendor directory.
 */
require_once dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require_once dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'JPIPAllow.php';

use kaleidpixel\JPIPAllow;

$before_str = <<<EOL
## IP address of its In-House server.
Allow from 103.xxx.xxx.xxx
Allow from 203.xxx.xxx.xxx
EOL;

$ip = new JPIPAllow(
	[
		'server'         => 'apache',
		'output_path'    => __DIR__ . DIRECTORY_SEPARATOR . '.htaccess',
		'add_before_str' => $before_str
	]
);

$ip->read();
