<?php


/**
 * Reward sales order invoice total model
 */
namespace Riki\Loyalty\Model\Total\Invoice;

use Magento\Sales\Model\Order\Invoice;

class Reward extends \Magento\Sales\Model\Order\Invoice\Total\AbstractTotal
{
    /**
     * Collect reward total for invoice
     *
     * @param Invoice $invoice
     * @return $this
     */
    public function collect(Invoice $invoice)
    {
        $order = $invoice->getOrder();
        $invoice->setGrandTotal($order->getGrandTotal());
         $invoice->setBaseGrandTotal($order->getBaseGrandTotal());

        return $this;
    }
}
