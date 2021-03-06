<?php

namespace ElfSundae\Multimail;

use Illuminate\Mail\MailServiceProvider as BaseServiceProvider;

class MailServiceProvider extends BaseServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->registerSwiftMailer();

        $this->registerMailer();
    }

    /**
     * Register the Mailer instance.
     */
    protected function registerMailer()
    {
        $this->app->singleton('mailer', function ($app) {
            // Once we have create the mailer instance, we will set a container instance
            // on the mailer. This allows us to resolve mailer classes via containers
            // for maximum testability on said classes instead of passing Closures.
            $mailer = new Mailer(
                $app['view'], $app['swift.mailer'], $app['events']
            );

            $this->setMailerDependencies($mailer, $app);

            // If a "from" address is set, we will set it on the mailer so that all mail
            // messages sent by the applications will utilize the same "from" address
            // on each one, which makes the developer's life a lot more convenient.
            $from = $app['config']['mail.from'];

            if (is_array($from) && isset($from['address'])) {
                $mailer->alwaysFrom($from['address'], $from['name']);
            }

            $to = $app['config']['mail.to'];

            if (is_array($to) && isset($to['address'])) {
                $mailer->alwaysTo($to['address'], $to['name']);
            }

            return $mailer;
        });

        $this->app->alias('mailer', Mailer::class);
    }

    /**
     * Set a few dependencies on the mailer instance.
     *
     * @param  \ElfSundae\Multimail\Mailer  $mailer
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function setMailerDependencies($mailer, $app)
    {
        parent::setMailerDependencies($mailer, $app);

        $mailer->setSwiftMailerManager($app['swift.manager']);
    }

    /**
     * Register the Swift Mailer instance.
     */
    public function registerSwiftMailer()
    {
        $this->registerSwiftTransport();

        $this->registerSwiftMailerManager();

        $this->app->bind('swift.mailer', function ($app) {
            return $app['swift.manager']->mailer();
        });
    }

    /**
     * Register the Swift Transport instance.
     */
    protected function registerSwiftTransport()
    {
        $this->app->singleton('swift.transport', function ($app) {
            return new TransportManager($app);
        });

        $this->app->alias('swift.transport', TransportManager::class);
    }

    /**
     * Register the Swift Mailer Manager instance.
     */
    protected function registerSwiftMailerManager()
    {
        $this->app->singleton('swift.manager', function ($app) {
            return (new SwiftMailerManager($app))
                ->setTransportManager($app['swift.transport']);
        });

        $this->app->alias('swift.manager', SwiftMailerManager::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array_merge(parent::provides(), [
            'swift.manager',
            Mailer::class,
            SwiftMailerManager::class,
            TransportManager::class,
        ]);
    }
}
