<?php
namespace Riki\ThirdPartyImportExport\Block\Adminhtml\Order\View;

class Info extends Generic
{
    /**
     * Get billing address of order
     *
     * @return string
     */
    public function getBillingAddress()
    {

        /**
         * @var \Riki\ThirdPartyImportExport\Model\Order
         */
        $order = $this->getOrder();

        return $order->getBillingAddress();

    }

    /**
     * Get shipping address of order
     *
     * @return string
     */
    public function getShippingAddress()
    {
        /**
         * @var \Riki\ThirdPartyImportExport\Model\Order
         */
        $order = $this->getOrder();

        /**
         * @var \Riki\ThirdPartyImportExport\Model\Shipping
         */
        $shipping = $order->getShipping();


        return $shipping->getShippingAddress();
    }

    /**
     * Get status label
     *
     * @return \Magento\Framework\Phrase
     */
    public function getStatusLabel()
    {
        /**
         * @var \Riki\ThirdPartyImportExport\Model\Order
         */
        $order = $this->getOrder();

        $status = $order->getOrderStatus();
        if ($status == 0) {
            $status =  __('Pre Order');
        } elseif ($status == 1) {
            $status =  __('Ordinary');
        } elseif ($status == 2) {
            $status =  __('Canceled');
        }

        return $status;
    }

    /**
     * Get billing phone number
     *
     * @return mixed
     */
    public function getBillingPhoneNumber()
    {
        /**
         * @var \Riki\ThirdPartyImportExport\Model\Order
         */
        $order = $this->getOrder();

        return $order->getPhoneNumber();
    }

    /**
     * Get shipping phone number
     *
     * @return mixed
     */
    public function getShippingPhoneNumber()
    {
        /**
         * @var \Riki\ThirdPartyImportExport\Model\Order
         */
        $order = $this->getOrder();

        /**
         * @ \Riki\ThirdPartyImportExport\Model\Order\Shipping
         */
        $shipping = $order->getShipping();

        return $shipping->getPhoneNumber();
    }
}
