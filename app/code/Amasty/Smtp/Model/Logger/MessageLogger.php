<?php
/**
 * @author    Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package   Amasty_Smtp
 */

namespace Amasty\Smtp\Model\Logger;

use Amasty\Smtp\Model\Log;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\ScopeInterface;

class MessageLogger
{
    const PATH_AMASTY_BACKUP_FILE = 'var/amasty_email_log_backups';

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Amasty\Smtp\Model\Logger\DebugLogger
     */
    protected $debugLogger;

    /**
     * Core Date
     *
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $coreDate;

    /**
     * @var \Amasty\Smtp\Helper\Data
     */
    protected $helper;

    /**
     * @var \Amasty\Smtp\Model\LogFactory
     */
    private $logFactory;

    /**
     * @var \Amasty\Smtp\Model\ResourceModel\Log
     */
    private $logResource;

    public function __construct(
        ObjectManagerInterface $objectManager,
        ScopeConfigInterface $scopeConfig,
        DebugLogger $debugLogger,
        \Amasty\Smtp\Helper\Data $helper,
        DateTime $coreDate,
        \Amasty\Smtp\Model\LogFactory $logFactory,
        \Amasty\Smtp\Model\ResourceModel\Log $logResource
    ) {
        $this->objectManager = $objectManager;
        $this->scopeConfig   = $scopeConfig;
        $this->coreDate      = $coreDate;
        $this->debugLogger   = $debugLogger;
        $this->helper        = $helper;
        $this->logFactory    = $logFactory;
        $this->logResource   = $logResource;
    }

    public function log(MessageInterface $message)
    {
        $storeId = $this->helper->getCurrentStore();

        if ($this->scopeConfig->isSetFlag(
            'amsmtp/general/log',
            ScopeInterface::SCOPE_STORE,
            $storeId
        )) {
            if (class_exists(\Zend\Mail\Message::class, false)) {
                $recipients = current(\Zend\Mail\Message::fromString($message->getRawMessage())->getTo());
                $recipients = array_keys($recipients);
                $body       = $message->getBody();

                if ($body instanceof \Zend\Mime\Message) {
                    $body = $body->generateMessage();
                } else {
                    $body = (string)$body;
                }
            } else {
                $recipients = $message->getRecipients();
                $body       = ($message->getBody()) ? $message->getBody()->getRawContent() : '';
            }

            $recipient = implode(', ', $recipients);

            /** @var Log $logMessage */
            $logMessage = $this->logFactory->create();
            $logMessage->setData(
                [
                    'created_at'      => $this->coreDate->gmtDate(),
                    'subject'         => $message->getSubject(),
                    'body'            => $body,
                    'recipient_email' => $recipient,
                    'status'          => Log::STATUS_PENDING
                ]
            );

            $this->logResource->save($logMessage);

            return $logMessage->getId();
        } else {
            return false;
        }
    }

    public function updateStatus($logId, $status)
    {
        $storeId = $this->helper->getCurrentStore();

        if ($this->scopeConfig->isSetFlag('amsmtp/general/log', ScopeInterface::SCOPE_STORE, $storeId)) {
            /** @var Log $logMessage */
            $logMessage = $this->logFactory->create();

            $this->logResource->load($logMessage, $logId);

            if ($logMessage->getId()) {
                $logMessage
                    ->setStatus($status)
                    ->save();
            }
        }
    }

    public function autoClear()
    {
        $days = $this->scopeConfig->getValue('amsmtp/clear/email');

        if ($days) {
            $dirFile  = self::PATH_AMASTY_BACKUP_FILE;
            $dirLocal = $this->helper->createFileLocal($dirFile);
            if ($dirLocal) {
                $this->debugLogger->log(__('Starting to auto clear email log (after %1 days)', $days));
                $this->logResource->clear($days, $dirLocal);
            }
        }
    }
}
