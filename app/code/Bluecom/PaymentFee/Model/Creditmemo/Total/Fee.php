<?php

namespace Bluecom\PaymentFee\Model\Creditmemo\Total;

use Magento\Sales\Model\Order\Creditmemo\Total\AbstractTotal;

class Fee extends AbstractTotal
{
    /**
     * Collect
     *
     * @param \Magento\Sales\Model\Order\Creditmemo $creditmemo create memo
     *
     * @return $this
     */
    public function collect(\Magento\Sales\Model\Order\Creditmemo $creditmemo)
    {
        $creditmemo->setFee(0);
        $creditmemo->setBaseFee(0);

        $amount = $creditmemo->getOrder()->getFee();
        $creditmemo->setFee($amount);

        $amount = $creditmemo->getOrder()->getBaseFee();
        $creditmemo->setBaseFee($amount);

        $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $creditmemo->getFee());
        $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $creditmemo->getBaseFee());

        return $this;
    }
}