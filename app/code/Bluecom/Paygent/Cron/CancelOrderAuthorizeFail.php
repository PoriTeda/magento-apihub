<?php

namespace Bluecom\Paygent\Cron;

class CancelOrderAuthorizeFail
{
    /**
     * @var \Bluecom\Paygent\Logger\Logger
     */
    protected $paygentLogger;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;
    /**
     * @var \Magento\Sales\Api\OrderManagementInterface
     */
    protected $orderManagement;
    /**
     * @var \Magento\Sales\Api\Data\OrderStatusHistoryInterface
     */
    protected $orderStatusHistory;

    public function __construct(
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Bluecom\Paygent\Model\Reauthorize $reauthorize,
        \Bluecom\Paygent\Logger\Logger $paygentLogger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Sales\Api\OrderManagementInterface $orderManagement,
        \Magento\Sales\Api\Data\OrderStatusHistoryInterface $orderStatusHistory
    ) {
        $this->dateTime = $dateTime;
        $this->reauthorize = $reauthorize;
        $this->paygentLogger = $paygentLogger;
        $this->timezone = $timezone;
        $this->paygentLogger->setTimezone(new \DateTimeZone($timezone->getConfigTimezone()));
        $this->scopeConfig = $scopeConfig;
        $this->orderManagement = $orderManagement;
        $this->orderStatusHistory = $orderStatusHistory;
    }
    
    public function execute()
    {
        $isEnabled = $this->scopeConfig->getValue('paygent_config/delete_fail/active');
        if(!$isEnabled) {
            return ;
        }
        $this->paygentLogger->info('======== START =========');
        $this->paygentLogger->info('Cron Cancel Order After four times re-authorisation Paygent failure');
        $collection = $this->getAllOrderAfter4Fails();
        if($collection) {
            foreach ($collection as $data) {
                $this->cancelOrderFail($data);
            }
        }
        $this->paygentLogger->info('======== END =========');
        return $this;
    }

    public function getAllOrderAfter4Fails()
    {
        $collection = $this->reauthorize->getCollection()
            ->addFieldToFilter('re_authorization_status', ['eq' => 0])
            ->addFieldToFilter('authorized_number', ['gteq' => 4]);

        if(!$collection->getSize()) {
            return false;
        }

        return $collection;
    }

    public function cancelOrderFail($data)
    {
        if($data->getOrderId()) {
            $historyComment = $this->orderStatusHistory->setComment(__('Cancel Order after 4 times re-authoisation failure.'))
                                                        ->setStatus('canceled');;
            $this->orderManagement->addComment($data->getOrderId(),$historyComment);
            $this->orderManagement->cancel($data->getOrderId());
        }
        return $this;
    }
}