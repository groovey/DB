<?php

namespace Groovey\DB\Providers;

use Pimple\Container as PimpleContainer;
use Pimple\ServiceProviderInterface;
use Silex\Application;
use Silex\Api\BootableProviderInterface;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Cache\CacheManager;
use Groovey\DB\DB;

class DBServiceProvider implements ServiceProviderInterface, BootableProviderInterface
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

        $app['db'] = function ($app) {

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

            $db = new DB($app, $capsule);

            if (isset($app['db.connection'])) {
                $db->connect($app['db.connection'], 'default');
            } elseif (isset($app['db.connections'])) {
                $db->connectMultiple($app['db.connections']);
            } elseif (isset($app['db.replication'])) {
                $db->connectReplication($app['db.replication'], $capsule);
            }

            return $capsule;
        };
    }

    public function boot(Application $app)
    {
        if ($app['db.eloquent']) {
            $app->before(function () use ($app) {
                $app['db']::connection();
            }, Application::EARLY_EVENT);
        }
    }
}
