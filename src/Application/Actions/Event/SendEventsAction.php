<?php

namespace App\Application\Actions\Event;

use App\Application\Actions\Action;
use PhpAmqpLib\Channel\AbstractChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;

class SendEventsAction extends Action
{
    private const EXCHANGE_NAME = 'events';

    /** @var string */
    private const QUEUE_PREFIX = 'account_';

    /** @var AbstractChannel $channel */
    private AbstractChannel $channel;

    /** @var AbstractConnection $connection */
    private AbstractConnection $connection;

    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);

        $this->connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
        $this->channel = $this->connection->channel();
        $this->channel->exchange_declare(
            self::EXCHANGE_NAME,
            AMQPExchangeType::DIRECT,
            false,
            true,
            false
        );
    }

    public function __destruct()
    {
        $this->channel->close();
        $this->connection->close();
    }

    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $events = json_decode($this->request->getBody(), true);
        foreach ($events as $event) {
            $queueName = self::QUEUE_PREFIX . $event['account_id'];
            $this->channel->queue_declare($queueName, false, true, false, false);
            $this->channel->queue_bind($queueName, self::EXCHANGE_NAME, $queueName);

            $msg = new AMQPMessage(json_encode($event), ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);
            $this->channel->basic_publish($msg, self::EXCHANGE_NAME, $queueName);
        }

        return $this->respondWithData();
    }
}
