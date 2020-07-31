<?php
namespace Riki\Rma\Plugin\Paygent\Model;

/**
 * Class Paygent
 *
 * @package Riki\Rma\Plugin\Paygent\Model
 * @deprecated
 */
class Paygent
{
    /**
     * @var \Riki\Rma\Plugin\Paygent\Helper\Data
     */
    protected $_paygentDataPlugin;

    /**
     * Paygent constructor.
     * @param \Riki\Rma\Plugin\Paygent\Helper\Data $paygentDataPlugin
     */
    public function __construct(
        \Riki\Rma\Plugin\Paygent\Helper\Data $paygentDataPlugin
    )
    {
        $this->_paygentDataPlugin = $paygentDataPlugin;
    }

    /**
     * @param \Bluecom\Paygent\Model\Paygent $subject
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param $amount
     * @return array
     */
    public function beforeRefund(\Bluecom\Paygent\Model\Paygent $subject, \Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        $this->_paygentDataPlugin->setIsRefundByPaygent(true);
        return [$payment, $amount];
    }
}