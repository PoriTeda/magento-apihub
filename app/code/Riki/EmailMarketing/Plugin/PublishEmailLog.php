<?php

namespace Riki\EmailMarketing\Plugin;

use Amasty\Smtp\Model\Log;

class PublishEmailLog
{
    /**
     * @var \Riki\EmailMarketing\Queue\Publisher\ResendFailedMail
     */
    private $resendFailedMailPublisher;

    /**
     * @var \Riki\EmailMarketing\Api\MailLog\ItemInterfaceFactory
     */
    private $itemFactory;

    public function __construct(
        \Riki\EmailMarketing\Queue\Publisher\ResendFailedMail $resendFailedMailPublisher,
        \Riki\EmailMarketing\Api\MailLog\ItemInterfaceFactory $itemFactory
    ) {
        $this->resendFailedMailPublisher = $resendFailedMailPublisher;
        $this->itemFactory = $itemFactory;
    }

    public function beforeUpdateStatus($subject, $logId, $status)
    {
        if($logId && $status == Log::STATUS_FAILED) {
            /** @var \Riki\EmailMarketing\Api\MailLog\ItemInterface $logItem */
            $logItem = $this->itemFactory->create();
            $logItem->setLogId($logId);

            $this->resendFailedMailPublisher->execute($logItem);
        }

        return null;
    }
}