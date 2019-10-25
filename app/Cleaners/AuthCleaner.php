<?php
namespace App\Cleaners;

use Hhxsv5\LaravelS\Illuminate\Cleaners\CleanerInterface;
use Mockery\Container;

class AuthCleaner implements CleanerInterface
{
    public function clean(Container $app, Container $snapshot)
    {
        if (!$app->offsetExists('auth')) {
            return;
        }
        $ref = new \ReflectionObject($app['auth']);
        if ($ref->hasProperty('guards')) {
            $guards = $ref->getProperty('guards');
        } else {
            $guards = $ref->getProperty('drivers');
        }
        $guards->setAccessible(true);
        $guards->setValue($app['auth'], []);

        $app->forgetInstance('auth.driver');
        Facade::clearResolvedInstance('auth.driver');
    }
}