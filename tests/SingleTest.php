<?php

use Silex\Application;
use Groovey\DB\Providers\DBServiceProvider;

class SingleTest extends PHPUnit_Framework_TestCase
{
    public $app;

    public function setUp()
    {
        $app = new Application();
        $app['debug'] = true;

        $app->register(new DBServiceProvider(), [
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

        $this->app = $app;
    }

    public function test()
    {
        $app = $this->app;

        Database::create();

        $results = $app['db']::table('users')->where('id', '>=', 1)->get();
        $this->assertInternalType('array', $results);

        $log = $app['db']::connection()->getQueryLog();

        Database::drop();
    }
}
