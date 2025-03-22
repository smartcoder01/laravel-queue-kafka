# Laravel Kafka Queue Driver

This package provides a Kafka-based queue driver for Laravel, allowing you to use Apache Kafka as a message queue for your Laravel applications. It integrates seamlessly with Laravel's queue system, enabling you to push jobs to Kafka and process them using Kafka consumers.

---

## Features

- **Kafka Queue Driver**: Use Kafka as a queue driver in Laravel.
- **Producer and Consumer Support**: Easily produce and consume messages from Kafka topics.
- **Configuration**: Customize Kafka connection settings via Laravel's configuration files.
- **Service Provider**: Automatically registers Kafka connectors and dependencies.
- **Job Handling**: Process jobs using Laravel's job handling system.

---

## Installation

You can install the package via Composer:

```bash
composer require smartcoder01/laravel-kafka-queue
```

---

## Configuration

### 1. Publish Configuration File

Publish the configuration file to your Laravel application:

```bash
php artisan vendor:publish --provider="Smartcoder01\Queue\Kafka\KafkaQueueServiceProvider"
```

This will create a `kafka.php` file in your `config` directory.

### 2. Update `.env`

Add the following Kafka configuration to your `.env` file:

```env
QUEUE_CONNECTION=kafka

KAFKA_BROKER_LIST=localhost:9092
KAFKA_GROUP_ID=laravel-queue-group
KAFKA_AUTO_OFFSET_RESET=earliest
KAFKA_QUEUE=default
```

### 3. Configure Kafka Connection

Update the `config/kafka.php` file to match your Kafka setup:

```php
return [
    'connections' => [
        'kafka' => [
            'broker_list' => env('KAFKA_BROKER_LIST', 'localhost:9092'),
            'group_id' => env('KAFKA_GROUP_ID', 'laravel-queue-group'),
            'auto_offset_reset' => env('KAFKA_AUTO_OFFSET_RESET', 'earliest'),
            'queue' => env('KAFKA_QUEUE', 'default'),
            'default_configuration' => [
                'enable.auto.commit' => 'true',
                // Add other Kafka configuration options here
            ],
        ],
    ],
];
```

---

## Usage

### 1. Setting Up Queues

To use Kafka as your queue driver, update the `QUEUE_CONNECTION` variable in your `.env` file:

```env
QUEUE_CONNECTION=kafka
```

### 2. Pushing Jobs to Kafka

You can push jobs to Kafka just like you would with any other Laravel queue driver:

```php
dispatch(new \App\Jobs\ProcessOrder($order));
```

Alternatively, you can use the `Queue` facade:

```php
use Illuminate\Support\Facades\Queue;

Queue::push(new \App\Jobs\ProcessOrder($order));
```

### 3. Consuming Jobs

To process jobs from Kafka, run the Laravel queue worker:

```bash
php artisan queue:work
```

This will start a worker that listens to the Kafka topic specified in your configuration.

---

## Advanced Configuration

### Customizing Kafka Producer and Consumer

You can customize the Kafka producer and consumer by modifying the `registerDependencies` method in the `KafkaQueueServiceProvider`:

```php
$this->app->bind('queue.kafka.producer', function ($app, $parameters) {
    $conf = new \RdKafka\Conf();
    $conf->set('metadata.broker.list', env('KAFKA_BROKER_LIST', 'localhost:9092'));
    return new \RdKafka\Producer($conf);
});

$this->app->bind('queue.kafka.consumer', function ($app, $parameters) {
    $conf = new \RdKafka\Conf();
    $conf->set('metadata.broker.list', env('KAFKA_BROKER_LIST', 'localhost:9092'));
    $conf->set('group.id', env('KAFKA_GROUP_ID', 'laravel-queue-group'));
    $conf->set('auto.offset.reset', env('KAFKA_AUTO_OFFSET_RESET', 'earliest'));
    return new \RdKafka\KafkaConsumer($conf);
});
```

### Handling Deadlocks

The package includes basic deadlock handling for job processing. If a deadlock is detected, the worker will retry the job after a short delay.

---

## Troubleshooting

### 1. Kafka Broker Not Reachable

Ensure that your Kafka broker is running and accessible. Check the `KAFKA_BROKER_LIST` configuration in your `.env` file.

### 2. Consumer Not Receiving Messages

- Verify that the `group.id` and `auto.offset.reset` settings are correct.
- Ensure that the Kafka topic exists and that the consumer is subscribed to the correct topic.

### 3. Producer Timeout

If the producer times out while waiting for acknowledgment, increase the timeout in the `waitForAck` method:

```php
protected function waitForAck()
{
    $timeout = 30 * 1000; // Increase timeout to 30 seconds
    // ...
}
```

---

## Contributing

Contributions are welcome! Please open an issue or submit a pull request if you have any improvements or bug fixes.

---

## License

This package is open-source software licensed under the [MIT License](LICENSE).

---

## Credits

- [SmartCoder01](https://github.com/SmartCoder01)
- [Laravel](https://laravel.com)
- [php-rdkafka](https://github.com/arnaud-lb/php-rdkafka)

---

Enjoy using Kafka with Laravel! 🚀