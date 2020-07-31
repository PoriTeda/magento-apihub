<?php

namespace Bluecom\PaymentFee\Model;

class PaymentFee extends \Magento\Framework\Model\AbstractModel
{
    const FEE_ENABLE = 1;
    const FEE_DISABLE = 0;

    /**
     * Model construct that should be used for object initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Bluecom\PaymentFee\Model\ResourceModel\PaymentFee');
    }

    /**
     * Load payment fee by code
     *
     * @param string $paymentCode payment code
     *
     * @return boolean
     */
    public function loadByCode($paymentCode)
    {

        return $this->getResource()->loadByCode($this, $paymentCode);
    }

    /**
     * Get Visibilities
     *
     * @return array
     */
    public static function getActive()
    {
        return
            [
            self::FEE_ENABLE => __('Enable'),
            self::FEE_DISABLE => __('Disable')
            ];
    }
}