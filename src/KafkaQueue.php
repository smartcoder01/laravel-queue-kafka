<?php

namespace Smartcoder01\Queue\Kafka;

use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Queue;
use RdKafka\Producer;
use RdKafka\KafkaConsumer;
use RdKafka\Message;
use Illuminate\Support\Facades\Log;

class KafkaQueue extends Queue implements QueueContract
{
    protected Producer $producer;
    protected KafkaConsumer $consumer;
    protected $defaultQueue;

    public function __construct(Producer $producer, KafkaConsumer $consumer, $defaultQueue)
    {
        $this->producer = $producer;
        $this->consumer = $consumer;
        $this->defaultQueue = $defaultQueue;
    }

    public function size($queue = null)
    {
        return 0;
    }

    public function push($job, $data = '', $queue = null)
    {
        $queue = $this->getQueue($queue);
        $payload = $this->createPayload($job, $data);

        return $this->pushRaw($payload, $queue);
    }

    public function pushRaw($payload, $queue = null, array $options = [])
    {
        $queue = $this->getQueue($queue);

        $topic = $this->producer->newTopic($queue);
        $topic->produce(RD_KAFKA_PARTITION_UA, 0, $payload);

        $this->waitForAck();

        return null;
    }

    public function later($delay, $job, $data = '', $queue = null)
    {
        $this->push($job, $data, $queue);
    }

    public function pop($queue = null)
    {
        $queue = $this->getQueue($queue);

        $this->consumer->subscribe([$queue]);

        $message = $this->consumer->consume(120 * 1000);

        if ($message->err === RD_KAFKA_RESP_ERR_NO_ERROR) {
            return new KafkaJob(
                $this->container,
                $this,
                $message,
                $queue
            );
        }

        return null;
    }

    protected function getQueue($queue)
    {
        return $queue ?: $this->defaultQueue;
    }

    protected function waitForAck()
    {
        $timeout = 10 * 1000;
        $startTime = microtime(true);

        while ($this->producer->getOutQLen() > 0) {
            $this->producer->poll(50);

            if ((microtime(true) - $startTime) * 1000 > $timeout) {
                throw new \RuntimeException("Kafka producer timeout: message not acknowledged");
            }
        }
    }

    public function getConsumer(): KafkaConsumer
    {
        return $this->consumer;
    }
}
