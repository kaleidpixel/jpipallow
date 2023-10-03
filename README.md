# JPIPAllow

A tool designed to generate a .htaccess file that exclusively allows access from Japanese IP addresses, ensuring web
content is accessible only within Japan.

# Document

Coding is quite simple. The options are only simple, so you won't get lost.

## Options

|                                 Option | Description                                                     |
|---------------------------------------:|:----------------------------------------------------------------|
|        **server**<br>_default: apache_ | Apache or Nginx                                                 |
|    **output_path**<br>_default: empty_ | File path including the name of the file to output the results. |
| **add_before_str**<br>_default: empty_ | .                                                               |
| **add_before_str**<br>_default: empty_ | .                                                               |

## Methods

|                 Method | Parameter                                                                            | Description                                                                               |
|-----------------------:|:-------------------------------------------------------------------------------------|:------------------------------------------------------------------------------------------|
|           **output()** | bool $echo = false<br>bool $force = false                                            | Create a list of IP addresses. If already created, cache it for one day.                  |
|  **ipListEndPoints()** | none                                                                                 | Wrapper method for the constant IP_LIST_ENDPOINTS.                                        |
| **getCIDRRangeIPv4()** | none                                                                                 | Calculate CIDR.                                                                           |
|           **is_cli()** | none                                                                                 | Check if the type of interface between the web server and PHP is CLI.                     |
| **curl_get_content()** | string $url = ''<br>array $header = []<br>string $method = 'GET'<br>array $data = [] | Retrieve the HTTP code and content of the specified URL.                                  |
|         **download()** | string $file_path = ''<br>string $mime_type = null                                   | Output the header in the web browser to download the file and initiate the file download. |

## Basic markup

What follows is the simplest coding.

```php
<?php
require_once dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'src/JPIPAllow.php';

use kaleidpixel\JPIPAllow;

$now        = date( 'Y-m-d' );
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

$ip->output(true);

```

The source code shown above will work on the built-in web server. It also operates in CLI, so choose whichever you prefer.

If you want to run it on the built-in web server, execute the command shown below and then access it with a web browser.

```shell
$ php -S localhost:8080

```

If you want to run it in CLI, execute the command shown below. The path where the file is outputted will be displayed as a result.

```shell
$ php ./example/index.php

```

# License

MIT License  
Copyright (c) 2023 Kaleid Pixel
