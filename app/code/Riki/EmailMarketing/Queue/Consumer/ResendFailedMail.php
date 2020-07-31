<?php

namespace Riki\EmailMarketing\Queue\Consumer;

use Amasty\Smtp\Model\Log;

class ResendFailedMail
{
    /**
     * @var \Amasty\Smtp\Model\LogFactory
     */
    private $logFactory;

    /**
     * @var \Amasty\Smtp\Model\ResourceModel\Log
     */
    private $logResource;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    private $inlineTranslation;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Zend\Log\Logger
     */
    private $logger;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;


    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * ResendFailedMail constructor.
     */
    public function __construct(
        \Amasty\Smtp\Model\LogFactory $logFactory,
        \Amasty\Smtp\Model\ResourceModel\Log $logResource,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->logFactory = $logFactory;
        $this->logResource = $logResource;
        $this->inlineTranslation = $inlineTranslation;
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->objectManager = $objectManager;
        $this->scopeConfig = $scopeConfig;
    }


    /**
     * @param \Riki\EmailMarketing\Api\MailLog\ItemInterface $messageItem
     * @return void
     */
    public function processMessage($messageItem)
    {
        /** @var Log $logItem */
        $logItem = $this->logFactory->create();

        $this->logResource->load($logItem, $messageItem->getLogId());

        if ($logItem->getId() && $logItem->getStatus() == Log::STATUS_FAILED) {

            try {
                $this->getLog()->info('Resend email logItem #' . $logItem->getId());
                $this->inlineTranslation->suspend();
                $this->preprareAndSendMessage($logItem);
                $this->inlineTranslation->resume();

                $logItem->setStatus(Log::STATUS_SENT);
            } catch (\Exception $exception) {
                $logItem->setStatus(Log::STATUS_RESEND_FAIL);
                $this->getLog()->crit('Resend email logItem #' . $logItem->getId() . ' error: '  . $exception->getMessage());
                $this->getLog()->crit($exception->getTraceAsString());
            }

            $logItem->save();
        }
    }


    /**
     * @param Log $logItem
     */
    private function preprareAndSendMessage($logItem)
    {
        try {
            $recipientEmail = array_map('trim', explode(",", $logItem->getRecipientEmail()));
            $sender = $this->scopeConfig->getValue('resend_email_queue/setting/identity');

            $this->transportBuilder->setTemplateIdentifier('riki_resend_email_template')
                ->setTemplateVars([])
                ->setTemplateOptions(
                    [
                        'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                        'store' => $this->storeManager->getStore()->getId(),
                    ]
                )
                ->setFrom($sender)
                ->addTo($recipientEmail);

            $transport = $this->transportBuilder->getTransport();
            $transport->setRelationEntityType('resend_failed_email');
            $transport->getMessage()->setSubject($logItem->getSubject())->setBodyText($logItem->getBody());
            $transport->sendMessage();

        } catch (\Exception $exception) {
            //Reset transportBuilder to avoid error from previous message
            $this->transportBuilder->resetTransportBuilder();
            throw $exception;
        } finally  {
            /** @var \Zend\Mail\Transport\Smtp $zendTransport */
            $zendTransport = $this->objectManager->get(\Zend\Mail\Transport\Smtp::class);
            $zendTransport->getConnection()->disconnect();
        }
    }

    private function getLog()
    {
        if ($this->logger == null) {
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/debug.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);
            $this->logger = $logger;
        }

        return $this->logger;
    }
}