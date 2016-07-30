<?php

use Silex\Application;
use Groovey\ORM\Providers\ORMServiceProvider;
use Groovey\ORM\Models\User;

class ModelsTest extends PHPUnit_Framework_TestCase
{
    public $app;

    public function setUp()
    {
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

        return $app;
    }

    public function test()
    {
        $app = $this->app;

        Database::create();

        $total = User::count();
        $this->assertEquals(0, $total);

        Database::drop();
    }
}
