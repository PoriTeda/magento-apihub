<?php

namespace Bluecom\PaymentFee\Model\Invoice\Total;

use Magento\Sales\Model\Order\Invoice\Total\AbstractTotal;

class Fee extends AbstractTotal
{

    /**
     * Collect
     *
     * @param \Magento\Sales\Model\Order\Invoice $invoice invoice
     *
     * @return $this
     */
    public function collect(\Magento\Sales\Model\Order\Invoice $invoice)
    {
        $invoice->setFee(0);
        $invoice->setBaseFee(0);

        $amount = $invoice->getOrder()->getFee();
        $invoice->setFee($amount);
        $amount = $invoice->getOrder()->getBaseFee();
        $invoice->setBaseFee($amount);

        $invoice->setGrandTotal($invoice->getGrandTotal() + $invoice->getFee());
        $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $invoice->getBaseFee());

        return $this;
    }
}