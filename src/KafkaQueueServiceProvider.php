<?php

namespace Smartcoder01\Queue\Kafka;

use Illuminate\Queue\QueueManager;
use Illuminate\Support\ServiceProvider;
use RdKafka\KafkaConsumer;

class KafkaQueueServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $queue = $this->app['queue'];
        $connector = new KafkaConnector($this->app);
        $queue->addConnector('kafka', function () use ($connector) {
            return $connector;
        });
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/kafka.php', 'queue.connections.kafka'
        );

        $this->registerDependencies();
    }


    protected function registerDependencies()
    {
        $this->app->bind('queue.kafka.topic_conf', function ($app) {
            return new \RdKafka\TopicConf();
        });
        $this->app->bind('queue.kafka.producer', function ($app, $parameters) {
            return new \RdKafka\Producer($parameters['conf']);
        });
        $this->app->bind('queue.kafka.conf', function ($app) {
            return new \RdKafka\Conf();
        });
        $this->app->bind('queue.kafka.consumer', function ($app, $parameters) {
            return new \RdKafka\KafkaConsumer($parameters['conf']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'queue.kafka.topic_conf',
            'queue.kafka.producer',
            'queue.kafka.consumer',
            'queue.kafka.conf',
        ];
    }

}
