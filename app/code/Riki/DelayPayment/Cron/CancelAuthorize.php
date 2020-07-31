<?php
namespace Riki\DelayPayment\Cron;

/**
 * Class CancelAuthorize
 * @package Riki\DelayPayment\Cron
 */
class CancelAuthorize
{
    /**
     * @var \Riki\DelayPayment\Model\OrderCancelAuthorize
     */
    protected $orderCancelAuthorize;
    /**
     * @var \Riki\DelayPayment\Helper\Data
     */
    protected $helperData;
    /**
     * @var \Riki\DelayPayment\Helper\Locker
     */
    protected $helperLocker;

    /**
     * CancelAuthorize constructor.
     * @param \Riki\DelayPayment\Helper\Data $helperData
     * @param \Riki\DelayPayment\Helper\Locker $helperLocker
     * @param \Riki\DelayPayment\Model\OrderCancelAuthorize $orderCancelAuthorize
     */
    public function __construct(
        \Riki\DelayPayment\Helper\Data $helperData,
        \Riki\DelayPayment\Helper\Locker $helperLocker,
        \Riki\DelayPayment\Model\OrderCancelAuthorize $orderCancelAuthorize
    ) {
        $this->helperData = $helperData;
        $this->helperLocker = $helperLocker;
        $this->orderCancelAuthorize = $orderCancelAuthorize;
    }

    /**
     * cron job execution method
     */
    public function execute()
    {
        if ($this->helperData->isEnable()) {
            $this->helperLocker->initialLocker();
            $cancelOrders = $this->orderCancelAuthorize->getAuthorizedOrders();
            if ($cancelOrders) {
                foreach ($cancelOrders as $order) {
                    $this->orderCancelAuthorize->cancelAuthorization($order);
                }
            }
            $this->helperLocker->deleteLockFile();
        }
    }
}
