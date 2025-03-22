<?php
namespace Smartcoder01\Queue\Kafka;

use Illuminate\Container\Container;
use Illuminate\Queue\Connectors\ConnectorInterface;
use RdKafka\Conf;
use RdKafka\Producer;
use RdKafka\KafkaConsumer;

class KafkaConnector implements ConnectorInterface
{
    private $container;

    /**
     * Constructor for KafkaConnector.
     *
     * @param Container $container The Laravel service container.
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Connects to Kafka and configures the producer and consumer.
     *
     * @param array $config Configuration array for Kafka connection.
     * @return KafkaQueue Returns an instance of KafkaQueue.
     */
    public function connect(array $config)
    {
        // Producer Configuration
        $producerConf = new Conf();
        $producerConf->set('metadata.broker.list', $config['broker_list']); // Set Kafka broker list

        // Create a Kafka producer instance using the Laravel container
        $producer = $this->container->makeWith('queue.kafka.producer', ['conf' => $producerConf]);

        // Consumer Configuration
        $consumerConf = new Conf();
        $consumerConf->set('metadata.broker.list', $config['broker_list']); // Set Kafka broker list
        $consumerConf->set('group.id', $config['group_id']); // Set consumer group ID
        $consumerConf->set('auto.offset.reset', $config['auto_offset_reset'] ?? 'earliest'); // Set offset reset policy
        $consumerConf->set('enable.auto.commit', $config['default_configuration']['enable.auto.commit'] ?? 'true'); // Enable auto commit

        // Create a Kafka consumer instance using the Laravel container
        $consumer = $this->container->makeWith('queue.kafka.consumer', ['conf' => $consumerConf]);

        // Topic Configuration
        $topicConf = $this->container->makeWith('queue.kafka.topic_conf', []); // Create topic configuration
        $topicConf->set('auto.offset.reset', 'largest'); // Set topic offset reset policy

        // General Kafka Configuration
        $conf = $this->container->makeWith('queue.kafka.conf', []); // Create general Kafka configuration
        $conf->set('group.id', $config['group_id'] ?? 'laravel-queue-kafka'); // Set group ID
        $conf->set('metadata.broker.list', $config['broker_list'] ?? 'kafka:29092'); // Set broker list with fallback
        foreach ($config['default_configuration'] as $key => $val) {
            $conf->set($key, $val); // Apply additional configuration settings
        }
        $conf->setDefaultTopicConf($topicConf); // Set default topic configuration

        return new KafkaQueue($producer, $consumer, $config['queue']);
    }
}