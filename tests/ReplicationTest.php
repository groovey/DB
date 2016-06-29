<?php

use Silex\Application;
use Groovey\ORM\Providers\ORMServiceProvider;

class ReplicationTest extends PHPUnit_Framework_TestCase
{
    private function init()
    {
        $app = new Application();
        $app['debug'] = true;

        $app->register(new ORMServiceProvider(), [
            'db.replication' => [
                'write' => [
                    'host'  => 'localhost',
                ],
                'read' => [
                    'host' => [
                        '88.88.88.81', // Invalid host
                        'localhost',
                    ],
                ],
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
        $app = $this->init();

        Database::create();

        $results = $app['db']::connection('write')->table('users')->where('id', '>=', 1)->get();
        $this->assertInternalType('array', $results);

        $results = $app['db']::connection('read')->table('users')->where('id', '>=', 1)->get();
        $this->assertInternalType('array', $results);

        Database::drop();
    }
}
