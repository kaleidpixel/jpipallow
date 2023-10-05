# JPIPAllow

A tool designed to generate a .htaccess file that exclusively allows access from Japanese IP addresses, ensuring web
content is accessible only within Japan.

# Document

Coding is quite simple. The options are only simple, so you won't get lost.

## Options

|                                 Option | Description                                                         |
|---------------------------------------:|:--------------------------------------------------------------------|
|        **server**<br>_default: apache_ | Apache or Nginx                                                     |
|                **ipv**<br>_default: 0_ | Specify the version of the IP Address with a single digit (4 or 6). |
|    **output_path**<br>_default: empty_ | File path including the name of the file to output the results.     |
| **add_before_str**<br>_default: empty_ | .                                                                   |
| **add_before_str**<br>_default: empty_ | .                                                                   |

## Methods

|     Method | Parameter                                 | Description                                                              |
|-----------:|:------------------------------------------|:-------------------------------------------------------------------------|
| **read()** | bool $echo = false<br>bool $force = false | Create a list of IP addresses. If already created, cache it for one day. |

## Basic markup

What follows is the simplest coding.

```php
<?php
require_once dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

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

$ip->read(true);

```

The source code shown above will work on the built-in web server. It also operates in CLI, so choose whichever you
prefer.

If you want to run it on the built-in web server, execute the command shown below and then access it with a web browser.

```shell
$ php -S localhost:8080

```

If you want to run it in CLI, execute the command shown below. The path where the file is outputted will be displayed as
a result.

```shell
$ php ./example/index.php

```

# License

MIT License  
Copyright (c) 2023 Kaleid Pixel
