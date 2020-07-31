<?php

namespace Riki\EmailMarketing\Model\Smtp\Logger;

use Amasty\Smtp\Model\Log;
use Magento\Framework\Mail\MessageInterface;
use Magento\Store\Model\ScopeInterface;

class MessageLogger extends \Amasty\Smtp\Model\Logger\MessageLogger
{
    public function log(MessageInterface $message,$data = ['relation_entity_type'=>'','relation_entity_id'=>'', 'header' => '', 'template_identifier' => ''])
    {
        $storeId = $this->helper->getCurrentStore();

        if ($this->scopeConfig->isSetFlag(
            'amsmtp/general/log', ScopeInterface::SCOPE_STORE, $storeId
        ) && $data['relation_entity_type'] != 'resend_failed_email') {

            if (class_exists(\Zend\Mail\Message::class, false)) {
                $recipients = current(\Zend\Mail\Message::fromString($message->getRawMessage())->getTo());
                $recipients = array_keys($recipients);
                $body = $message->getBody();

                if ($body instanceof \Zend\Mime\Message) {
                    $body = $body->generateMessage();
                } else {
                    $body = (string) $body;
                }
            } else {
                $recipients = $message->getRecipients();
                $body = ($message->getBody()) ? $message->getBody()->getRawContent() : '';
            }

            $recipient = implode(', ', $recipients);

            /** @var Log $logMessage */
            $logMessage = $this->objectManager->create('Amasty\Smtp\Model\Log');
            $logMessage->setData([
                'created_at'        => $this->coreDate->gmtDate(),
                'subject'           => $message->getSubject(),
                'body'              => $body,
                'recipient_email'   => $recipient,
                'status'            => Log::STATUS_PENDING,
                'relation_entity_type'        => $data['relation_entity_type'],
                'relation_entity_id'         => $data['relation_entity_id']
            ]);

            $logMessage->save();

            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/investigate_email.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $logger->info('Log Data for Email Log #' . $logMessage->getId());
            $logger->info('Template Identifier: ' . $data['template_identifier']);
            $logger->info('Header: ' . $data['header']);

            return $logMessage->getId();
        } else
            return false;
    }
}
