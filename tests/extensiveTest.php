<?php

use Silex\Application;
use Groovey\ORM\Providers\ORMServiceProvider;
use Groovey\ORM\Models\User;
use Groovey\ORM\DB;

class extensiveTest extends PHPUnit_Framework_TestCase
{
    protected $app;

    const NAME = 'Groovey';

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

        $this->app = $app;

        return parent::setUp();
    }

    public function testIsLoaded()
    {
        $this->assertInstanceOf('\Illuminate\Container\Container', $this->app['db.container']);
        $this->assertInstanceOf('\Illuminate\Events\Dispatcher', $this->app['db.dispatcher']);
        $this->assertInternalType('array', $this->app['db.connection']);
    }

    public function testConnection()
    {
        $app  = $this->app;
        $conn = $app['db']->connection();
        $this->assertInstanceOf('\Illuminate\Database\Connection', $conn);
    }

    public function testCreateTable()
    {
        Database::create();
    }

    public function testRawQueries()
    {
        // Insert
        $inserted = DB::insert(
                        'INSERT INTO users (name) VALUES (:name)',
                        ['name' => 'Groovey']
                    );

        $this->assertTrue($inserted);

        // Last Insert ID
        $id = DB::connection()->getPdo()->lastInsertId();
        $this->assertEquals(1, $id);

        // Select
        $result = DB::select('SELECT * FROM users WHERE id = ?', [$id]);
        $this->assertCount(1, $result);
        $this->assertEquals($result[0]->name, 'Groovey');

        // Delete
        $deleted = DB::delete('DELETE FROM users WHERE id = ?', [$id]);
        $this->assertEquals(1, $deleted);

        // Count
        $count = DB::selectOne('SELECT COUNT(*) as total FROM users');
        $this->assertEquals(0, $count->total);
    }

    public function testQueryBuilder()
    {
        $truncated = DB::table('users')->truncate();
        $this->assertNull($truncated);

        // Insert
        $id = DB::table('users')->insertGetId([
                'name' => static::NAME,
            ]
        );
        $this->assertEquals($id, 1);

        // Select
        $result = DB::table('users')->where('id', $id)->get();
        $this->assertCount(1, $result);
        $this->assertEquals(static::NAME, $result[0]->name);

        // Delete
        $deleted = DB::table('users')->delete($id);
        $this->assertEquals(1, $deleted);

        // Count
        $count = DB::table('users')->count();
        $this->assertEquals(0, $count);
    }

    public function testEloquentORM()
    {
        DB::table('users')->truncate();

        // Insert
        $user = new User();
        $user->name = static::NAME;
        $saved = $user->save();
        $this->assertTrue($saved);

        $id = $user->id;
        $this->assertEquals(1, $id);

        // Select
        $user = User::find($id);
        $this->assertInstanceOf('\Groovey\ORM\Models\User', $user);
        $this->assertEquals(static::NAME, $user->name);

        // Delete
        $deleted = User::destroy($id);
        $this->assertEquals(1, $deleted);

        // Count
        $count = User::count();
        $this->assertEquals(0, $count);
    }

    public function testQueryLogs()
    {
        $logs = DB::getQueryLog();
        $this->assertInternalType('array', $logs);
        $this->assertCount(count($logs), $logs);
        $this->assertInternalType('string', $logs[0]['query']);
    }

    public function testClear()
    {
        Database::drop();
    }
}
