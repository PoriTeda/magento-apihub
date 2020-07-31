<?php
namespace Riki\AdvancedInventory\Plugin\Quote\Model\QuoteManagement;

use Riki\Subscription\Model\Emulator\Config;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus;

class OutOfStockOrder
{
    /**
     * @var bool
     */
    protected $isEnabled;

    /**
     * @var \Riki\AdvancedInventory\Helper\OutOfStock
     */
    protected $outOfStockHelper;

    /**
     * @var \Riki\Framework\Helper\Scope
     */
    protected $scopeHelper;

    /**
     * OutOfStockOrder constructor.
     *
     * @param \Riki\Framework\Helper\Scope $scopeHelper
     * @param \Riki\AdvancedInventory\Helper\OutOfStock $outOfStockHelper
     */
    public function __construct(
        \Riki\Framework\Helper\Scope $scopeHelper,
        \Riki\AdvancedInventory\Helper\OutOfStock $outOfStockHelper
    ) {
        $this->scopeHelper = $scopeHelper;
        $this->outOfStockHelper = $outOfStockHelper;
        $this->init();
    }

    /**
     * Initialize
     *
     * @return void
     */
    public function init()
    {
        $this->setIsEnabled(true);
    }

    /**
     * Get isEnabled
     *
     * @return bool
     */
    public function getIsEnabled()
    {
        return $this->isEnabled;
    }

    /**
     * Set isEnabled
     *
     * @param $isEnabled
     *
     * @return bool
     */
    public function setIsEnabled($isEnabled)
    {
        $this->isEnabled = $isEnabled;
        return $this->isEnabled;
    }

    /**
     * Prevent generate order if original order is not payment collected
     *
     * @param \Magento\Quote\Model\QuoteManagement $subject
     * @param \Magento\Quote\Model\Quote $quote
     * @param array $orderData
     *
     * @return mixed
     */
    public function beforeSubmit(
        \Magento\Quote\Model\QuoteManagement $subject,
        \Magento\Quote\Model\Quote $quote,
        $orderData = []
    ) {
        if (!$this->getIsEnabled()) {
            return [$quote, $orderData];
        }

        if ($quote->getResource()->getMainTable() == Config::getCartTmpTableName()) {
            // no apply for simulate subscription order
            return [$quote, $orderData];
        }

        $func = \Riki\AdvancedInventory\Model\Queue\OosConsumer::class . '::initialize';
        $scopeData = $this->scopeHelper->isInFunction($func);

        if (!isset($scopeData['outOfStock']) || !isset($scopeData['quote'])) {
            return [$quote, $orderData];
        }

        if (!$scopeData['outOfStock'] instanceof \Riki\AdvancedInventory\Model\OutOfStock) {
            return [$quote, $orderData];
        }

        if (!$scopeData['quote'] instanceof \Magento\Quote\Model\Quote) {
            return [$quote, $orderData];
        }

        if ($scopeData['quote']->getId() != $quote->getId()) {
            return [$quote, $orderData];
        }

        $order = $this->outOfStockHelper->getOriginalOrder($scopeData['outOfStock']);
        $allowedStatus = [
            OrderStatus::STATUS_ORDER_NOT_SHIPPED,
            OrderStatus::STATUS_ORDER_COMPLETE,
            OrderStatus::STATUS_ORDER_IN_PROCESSING,
            OrderStatus::STATUS_ORDER_PARTIALLY_SHIPPED,
        ];

        if (!$order || in_array($order->getStatus(), $allowedStatus)) {
            return [$quote, $orderData];
        }

        $methodCode = $this->outOfStockHelper->getPaymentMethodCode($scopeData['outOfStock']);
        if ($methodCode == \Bluecom\Paygent\Model\Paygent::CODE) {
            // paygent, should no authorize, set remote_ip to null @see \Bluecom\Paygent\Model\Paygent::initialize
            $quote->setRemoteIp(null);
        }

        return [$quote, $orderData];
    }
}