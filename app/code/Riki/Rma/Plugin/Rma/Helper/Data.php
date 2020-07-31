<?php
namespace Riki\Rma\Plugin\Rma\Helper;

class Data
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $orderHelper;

    public function __construct(
        \Riki\Sales\Helper\Order $orderHelper
    ) {
        $this->orderHelper = $orderHelper;
    }

    /**
     * Can create rma
     * logic
     *      default Magento logic
     *      delay payment logic: can not create return if order not capture
     *
     * @param \Magento\Rma\Helper\Data $subject
     * @param  int|\Magento\Sales\Model\Order $order
     * @param  bool $forceCreate - set yes when you don't need to check config setting (for admin side)
     * @return bool
     */
    public function aroundCanCreateRma(
        \Magento\Rma\Helper\Data $subject,
        \Closure $proceed,
        $order,
        $forceCreate = false
    ) {
        if (!$order) {
            return false;
        }

        /*for the case that $order parameter is entity id of order*/
        if (!$order instanceof \Magento\Sales\Model\Order) {
            $order = $this->orderHelper->getOrderById($order);

            if (!$order) {
                return false;
            }
        }

        /*for the case that current order is delay payment order*/
        if ($this->orderHelper->isDelayPaymentOrder($order)) {
            if (!$this->orderHelper->isDelayPaymentOrderAllowedReturn($order)) {
                return false;
            }
        }

        return $proceed($order, $forceCreate);
    }
}