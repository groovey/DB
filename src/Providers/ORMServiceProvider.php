<?php

namespace Groovey\ORM\Providers;

use Pimple\Container as PimpleContainer;
use Pimple\ServiceProviderInterface;
use Silex\Application;
use Silex\Api\BootableProviderInterface;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Cache\CacheManager;

class ORMServiceProvider implements ServiceProviderInterface, BootableProviderInterface
{
    public function register(PimpleContainer $app)
    {
        $app['db.global']   = true;
        $app['db.eloquent'] = true;

        $app['db.container'] = function () {
            return new Container();
        };

        $app['db.dispatcher'] = function ($app) {
            return new Dispatcher($app['db.container']);
        };

        if (class_exists('Illuminate\Cache\CacheManager')) {
            $app['db.cache_manager'] = function ($app) {
                return new CacheManager($app['db.container']);
            };
        }

        $app['db'] = function ($name) use ($app) {

            $capsule = new Capsule($app['db.container']);
            $capsule->setEventDispatcher($app['db.dispatcher']);

            if (isset($app['db.cache_manager']) && isset($app['db.cache'])) {
                $capsule->setCacheManager($app['db.cache_manager']);
                foreach ($app['db.cache'] as $key => $value) {
                    $app['db.container']->offsetGet('config')->offsetSet('cache.'.$key, $value);
                }
            }

            if ($app['db.global']) {
                $capsule->setAsGlobal();
            }

            if ($app['db.eloquent']) {
                $capsule->bootEloquent();
            }

            if (isset($app['db.connection'])) {
                $this->setSingleConnection($app, $capsule);
            } elseif (isset($app['db.connections'])) {
                $this->setMultipleConnection($app, $capsule);
            } elseif (isset($app['db.replication'])) {
                $this->setReplicationDefaultConnection($app, $capsule);
                $this->setReplicationConnection($app, $capsule, 'write');
                $this->setReplicationConnection($app, $capsule, 'read');
            }

            return $capsule;
        };
    }

    private function setSingleConnection(Application $app, Capsule $capsule)
    {
        $server  = $app['db.connection'];
        $logging = $server['logging'];
        unset($server['logging']);

        $capsule->addConnection($server, 'default');

        if ($logging) {
            $capsule->connection('default')->enableQueryLog();
        } else {
            $capsule->connection('default')->disableQueryLog();
        }
    }

    private function setMultipleConnection(Application $app, Capsule $capsule)
    {
        $servers = $app['db.connections'];
        foreach ($servers as $key => $server) {
            $logging = $server['logging'];
            unset($server['logging']);

            $capsule->addConnection($server, $key);

            if ($logging) {
                $capsule->connection($key)->enableQueryLog();
            } else {
                $capsule->connection($key)->disableQueryLog();
            }
        }
    }

    private function setReplicationDefaultConnection(Application $app, Capsule $capsule, $name = 'default')
    {
        $server  = $app['db.replication'];
        $write  = $app['db.replication']['write']['host'];

        $server['host'] = $write;

        unset($server['write']);
        unset($server['read']);

        $this->connect($capsule, $server, 'default');
    }

    private function setReplicationConnection(Application $app, Capsule $capsule, $name = 'default')
    {
        $server = $app['db.replication'];
        $hosts  = $app['db.replication'][$name]['host'];

        unset($server['write']);
        unset($server['read']);

        if ($name == 'write') {
            $hosts = [$hosts];
        }

        $connected = false;

        while (!$connected && count($hosts)) {
            $key  = array_rand($hosts);
            $host = $hosts[$key];

            $server['host'] = $host;

            $status = $this->connect($capsule, $server, $name);

            if ($status === true) {
                break;
            }

            unset($hosts[$key]);
        }
    }

    private function connect(Capsule $capsule, array $server, $name)
    {
        $logging = $server['logging'];
        unset($server['logging']);

        $status = $this->checkConnection($server);
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

    private function checkConnection($server)
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

    public function boot(Application $app)
    {
        if ($app['db.eloquent']) {
            $app->before(function () use ($app) {
                $app['db'];
            }, Application::EARLY_EVENT);
        }
    }
}
