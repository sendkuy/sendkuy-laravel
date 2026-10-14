<?php

namespace SendKuy\Laravel;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;
use SendKuy\Laravel\Notifications\SendKuyChannel;
use SendKuy\SendKuy;

final class SendKuyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/sendkuy.php', 'sendkuy');

        $this->app->singleton(SendKuy::class, static function (Container $app): SendKuy {
            $config = $app->make(Repository::class)->get('sendkuy');

            return ClientFactory::make(is_array($config) ? $config : []);
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/webhook.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../config/sendkuy.php' => config_path('sendkuy.php')], 'sendkuy-config');
        }

        Notification::resolved(static function (ChannelManager $channels): void {
            $channels->extend('sendkuy', fn (Container $app): SendKuyChannel => $app->make(SendKuyChannel::class));
        });
    }
}
