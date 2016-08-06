# DB

Groovey DB Package

## Installation

    $ composer require groovey/db

## Usage

```php
 <?php

require_once __DIR__.'/vendor/autoload.php';

use Silex\Application;
use Groovey\DB\Providers\DBServiceProvider;

$app = new Application();
$app['debug'] = true;

$app->register(new DBServiceProvider(), [
    'db.connection' => [
        'host'      => 'localhost',
        'driver'    => 'mysql',
        'database'  => 'test_db',
        'username'  => 'root',
        'password'  => '',
        'charset'   => 'utf8',
        'collation' => 'utf8_unicode_ci',
        'prefix'    => '',
        'logging'   => true,
    ],
]);

$results = $app['db']::table('users')->where('id', '>=', 1)->get();

print_r($results);
```

## Documentation

Visit Laravel's database for more info:
https://laravel.com/docs/master/database