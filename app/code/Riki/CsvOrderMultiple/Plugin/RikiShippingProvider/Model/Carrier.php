<?php

namespace Riki\CsvOrderMultiple\Plugin\RikiShippingProvider\Model;

use Riki\Sales\Model\Config\Source\OrderType;

class Carrier
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(\Magento\Framework\Registry $registry)
    {
        $this->_registry = $registry;
    }

    /**
     * set quote object for shipping carrier model
     *
     * @param \Riki\ShippingProvider\Model\Carrier $subject
     * @param \Magento\Quote\Model\Quote\Address\RateRequest $request
     * @return array
     */
    public function beforeCalculateFeeForEachAddress(
        \Riki\ShippingProvider\Model\Carrier $subject,
        $request
    )
    {
        $requestItems = $request->getAllItems();

        if (
            count($requestItems) &&
            isset($requestItems[0]) &&
            $requestItems[0] instanceof \Magento\Quote\Model\Quote\Item
        ) {

            $quote = $requestItems[0]->getQuote();
            if ($quote->getData('is_csv_import_order_flag')==true || $quote->getData('original_unique_id') !='')
            {
                /**
                 * If order type ==2 or order type = 3
                 * Set shipping free= 0
                 */
                if (!isset($request['order_type'])) {
                    $orderType = $quote->getOrderType();
                    if ($orderType == OrderType::ORDER_TYPE_REPLACEMENT || $orderType == OrderType::ORDER_TYPE_FREE_SAMPLE) {
                        $quote->setData('free_shipping', 1);
                        $request->setData('free_shipping', 1);
                    }else if($orderType == OrderType::ORDER_TYPE_NORMAL ) {
                        if($quote->getData('free_shipping')==1)
                        {
                            $quote->setData('free_shipping', 1);
                            $request->setData('free_shipping', 1);
                        }else {
                            $quote->setData('free_shipping', 0);
                            $request->setData('free_shipping', 0);
                        }
                    }
                }
                $subject->setQuote($quote);
            }
        }

        return [$request];
    }
}