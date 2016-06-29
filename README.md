## ORM
A Silex 2 service provider that uses the Laravel's orm component.

## Installation

  composer require groovey/orm

## Usage

```php
        $app = new Application();
        $app['debug'] = true;

        $app->register(new ORMServiceProvider(), [
            'db.connection' => [
                'host'      => 'localhost',
                'driver'    => 'mysql',
                'database'  => 'test',
                'username'  => 'root',
                'password'  => '',
                'charset'   => 'utf8',
                'collation' => 'utf8_unicode_ci',
                'prefix'    => '',
                'logging'   => true,
            ],
        ]);
        
        $results = $app['db']::table('users')->where('id', '>=', 1)->get();
        
        return $results;
```

## Reading

 Visit Laravel's database for more documentation https://laravel.com/docs/master/database
