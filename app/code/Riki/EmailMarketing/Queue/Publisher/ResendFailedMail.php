<?php

namespace Riki\EmailMarketing\Queue\Publisher;

class ResendFailedMail
{
    const TOPIC_NAME = 'riki.mail.resend';

    /**
     * @var \Magento\Framework\MessageQueue\PublisherInterface
     */
    private $publisher;
    /**
     * @param \Magento\Framework\MessageQueue\PublisherInterface $publisher
     */
    public function __construct(\Magento\Framework\MessageQueue\PublisherInterface $publisher)
    {
        $this->publisher = $publisher;
    }

    public function execute(\Riki\EmailMarketing\Api\MailLog\ItemInterface $item)
    {
        $this->publisher->publish(static::TOPIC_NAME, $item);
    }
}