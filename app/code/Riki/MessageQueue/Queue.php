<?php

namespace Riki\MessageQueue;

use Magento\Framework\Amqp\Config;
use PhpAmqpLib\Message\AMQPMessage;
use Magento\Framework\MessageQueue\EnvelopeFactory;
use Psr\Log\LoggerInterface;
use Riki\MessageQueue\Helper\QueueDataHelper;

/**
 * Class Queue
 */
class Queue extends \Magento\Framework\Amqp\Queue
{
    /**
     * @var Config
     */
    private $amqpConfig;

    /**
     * @var string
     */
    private $queueName;

    /**
     * @var EnvelopeFactory
     */
    private $envelopeFactory;
    /**
     * @var QueueDataHelper
     */
    private $queueDataHelper;

    public function __construct(
        Config $amqpConfig,
        EnvelopeFactory $envelopeFactory,
        string $queueName,
        LoggerInterface $logger,
        QueueDataHelper $queueDataHelper
    )
    {
        parent::__construct($amqpConfig, $envelopeFactory, $queueName, $logger);
        $this->amqpConfig = $amqpConfig;
        $this->envelopeFactory = $envelopeFactory;
        $this->queueName = $queueName;
        $this->queueDataHelper = $queueDataHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function subscribe($callback)
    {
        $callbackConverter = function (AMQPMessage $message) use ($callback) {
            // @codingStandardsIgnoreStart
            $properties = array_merge(
                $message->get_properties(),
                [
                    'topic_name' => $message->delivery_info['routing_key'],
                    'delivery_tag' => $message->delivery_info['delivery_tag'],
                ]
            );
            // @codingStandardsIgnoreEnd
            $envelope = $this->envelopeFactory->create(['body' => $message->body, 'properties' => $properties]);

            if ($callback instanceof \Closure) {
                $callback($envelope);
            } else {
                call_user_func($callback, $envelope);
            }
        };

        $channel = $this->amqpConfig->getChannel();
        // @codingStandardsIgnoreStart
        $channel->basic_consume($this->queueName, '', false, false, false, false, $callbackConverter);
        // @codingStandardsIgnoreEnd
        while (!$this->queueDataHelper->isDisable() && count($channel->callbacks)) {
            $channel->wait();
        }
    }
}