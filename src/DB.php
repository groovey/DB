<?php

namespace Groovey\DB;

use Illuminate\Database\Capsule\Manager as Capsule;

class DB extends Capsule
{
    private $capsule;
    private $app;
    public function __construct($app, Capsule $capsule)
    {
        $this->app = $app;
        $this->capsule = $capsule;
    }

    public function connect(array $server, $name)
    {
        $capsule = $this->capsule;

        $logging = $server['logging'];
        unset($server['logging']);

        $status = $this->check($server);
        if (!$status) {
            return false;
        }

        $capsule->addConnection($server, $name);

        if ($logging) {
            $capsule->connection($name)->enableQueryLog();
        } else {
            $capsule->connection($name)->disableQueryLog();
        }

        return true;
    }

    public function connectMultiple(array $servers)
    {
        foreach ($servers as $key => $server) {
            $this->connect($server, $key);
        }
    }

    public function connectReplication(array $server)
    {
        $write = $server['write']['host'];
        $reads = $server['read']['host'];

        unset($server['write']);
        unset($server['read']);

        // Establish a 'default' connection
        $this->connect(array_merge(['host' => $write], $server), 'default');

        // Establish a 'write' connection
        $this->connect(array_merge(['host' => $write], $server), 'write');

        // Establish a 'read' connection
        $connected = false;
        while (!$connected && count($reads)) {
            $key  = array_rand($reads);
            $host = $reads[$key];

            $server['host'] = $host;
            $connected = $this->connect(array_merge(['host' => $host], $server), 'read');

            if ($connected === true) {
                break;
            }
            unset($reads[$key]);
        }
    }

    public function check($server)
    {
        $host     = $server['host'];
        $username = $server['username'];
        $password = $server['password'];

        $conn = @new \mysqli($host, $username, $password);

        if ($conn->connect_error) {
            return false;
        }

        return true;
    }
}
