<?php

namespace Smartcoder01\Queue\Kafka;

use Exception;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Database\DetectsDeadlocks;
use Illuminate\Queue\Jobs\Job;
use Illuminate\Queue\Jobs\JobName;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RdKafka\Message;


class KafkaJob extends Job implements JobContract
{
    protected $connection;
    protected $queue;
    protected $message;
    protected $job;

    public function __construct(Container $container, KafkaQueue $queue, Message $message, $queueName)
    {
        $this->container = $container;
        $this->queue = $queue;
        $this->message = $message;
        $this->queueName = $queueName;
        $this->connection = $queue;
    }

    public function handle()
    {
        $payload = json_decode($this->message->payload, true);

        if ($payload && isset($payload['job'])) {
            $this->resolveAndRun($payload);
        }
    }

    public function getJobId()
    {
        return $this->message->key;
    }

    public function getRawBody()
    {
        return $this->message->payload;
    }

    public function delete()
    {
        try {
            parent::delete();
            $this->queue->getConsumer()->commitAsync($this->message);
        } catch (\RdKafka\Exception $exception) {
            throw new KafkaQueueException('Could not delete job from the queue', 0, $exception);
        }
    }

    public function release($delay = 0)
    {
        parent::release($delay);

        $this->delete();
        $body = $this->payload();
        if (isset($body['data']['command']) === true) {
            $job = $this->unserialize($body);
        } else {
            $job = $this->getName();
        }
        $data = $body['data'];
        if ($delay > 0) {
            $this->connection->later($delay, $job, $data, $this->getQueue());
        } else {
            $this->connection->push($job, $data, $this->getQueue());
        }
    }

    public function attempts()
    {
        return $this->job ? $this->job->attempts() : 1;
    }

    public function getQueue()
    {
        return $this->queueName;
    }

    private function unserialize(array $body)
    {
        try {
            return unserialize($body['data']['command']);
        } catch (Exception $exception) {
            if (
                $this->causedByDeadlock($exception)
                || Str::contains($exception->getMessage(), ['detected deadlock'])
            ) {
                sleep($this->connection->getConfig()['sleep_on_deadlock']);
                return $this->unserialize($body);
            }
            throw $exception;
        }
    }
    
    
}
