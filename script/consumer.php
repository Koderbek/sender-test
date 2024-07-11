<?php

declare(strict_types=1);

use PhpAmqpLib\Connection\AMQPStreamConnection;

require __DIR__ . '/../vendor/autoload.php';

class EventConsumer
{
    /** @var int */
    private const USERS_COUNT = 1000;

    /** @var string */
    private const QUEUE_PREFIX = 'account_';

    /**
     * @param array $event
     * @return void
     */
    private function processEvent(array $event): void
    {
        sleep(1);
        echo "Processed event: {$event['event_id']} for account: {$event['account_id']}" . PHP_EOL;
    }

    /**
     * @param string $queueName
     * @return void
     * @throws Exception
     */
    function worker(string $queueName)
    {
        $connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
        $channel = $connection->channel();
        $channel->queue_declare($queueName, false, true, false, false);
        $callback = function ($msg) {
            $event = json_decode($msg->body, true);
            $this->processEvent($event);
            $msg->ack();
        };

        $channel->basic_qos(null, 1, null);
        $channel->basic_consume($queueName, '', false, false, false, false, $callback);
        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }

    /**
     * @return void
     * @throws Exception
     */
    public function run(): void
    {
        $workers = [];
        for ($account_id = 1; $account_id <= self::USERS_COUNT; $account_id++) {
            $queueName = self::QUEUE_PREFIX . $account_id;
            $pid = pcntl_fork();
            if ($pid == -1) {
                die('could not fork');
            }

            if ($pid) {
                $workers[] = $pid;
            } else {
                $this->worker($queueName);
                exit(0);
            }
        }

        foreach ($workers as $pid) {
            pcntl_waitpid($pid, $status);
        }
    }
}

(new EventConsumer())->run();
