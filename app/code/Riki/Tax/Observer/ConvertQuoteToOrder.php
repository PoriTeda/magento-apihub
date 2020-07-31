<?php

namespace Riki\Tax\Observer;

use Magento\Framework\Event\ObserverInterface;

class ConvertQuoteToOrder implements ObserverInterface
{

    /**
     * @var \Magento\Tax\Model\Calculation
     */
    protected $_taxCalculation;

    protected $_scopeConfig;

    protected $_taxClassIdToRate = [];

    /**
     * @param \Magento\Tax\Model\Calculation $taxCalculation
     */
    public function __construct(
        \Magento\Tax\Model\Calculation $taxCalculation,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ){
        $this->_taxCalculation = $taxCalculation;
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * calculate tax of authority for order
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getOrder();

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getQuote();

        $customerId = $quote->getCustomerId();
        $billingAddress = $quote->getBillingAddress();
        $shippingAddress = $quote->getShippingAddress();

        $storeId = $quote->getStoreId();

        $request = $this->_taxCalculation->getRateRequest(
            $shippingAddress,
            $billingAddress,
            null,
            $storeId,
            $customerId
        );

        $taxRateToItemTotal = [];
        $taxRateIndexes = [];

        $giftWrappingFee = 0;

        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        foreach($quote->getAllItems() as $quoteItem){

            if($quoteItem->getParentItemId())
                break;

            $product = $quoteItem->getProduct();

            $taxClassId = $product->getData('tax_class_id');

            $taxRate = $this->_getRateByTaxClassId($taxClassId, $request);

            $rateIndex = false;

            foreach($taxRateIndexes as $index   =>  $rate){
                if($taxRate == $rate){
                    $rateIndex = $index;
                    break;
                }
            }

            if($rateIndex === false){
                $taxRateIndexes[] = $taxRate;
                $rateIndex = max (count($taxRateIndexes) - 1, 0);
            }

            if(!isset($taxRateToItemTotal[$rateIndex]))
                $taxRateToItemTotal[$rateIndex] = 0;

            $taxRateToItemTotal[$rateIndex] += $quoteItem->getRowTotalInclTax() - $quoteItem->getDiscountAmount();

            //
            $giftWrappingFee += ($quoteItem->getGwPrice() + $quoteItem->getGwTaxAmount()) * $quoteItem->getQty();
        }

        $taxAuthority = 0;

        foreach($taxRateToItemTotal as $rateIndex   => $rateTotal){
            $taxAuthority += floor($rateTotal - ($rateTotal / (1 + ($taxRateIndexes[$rateIndex] / 100))));
        }

        //$taxAuthority += floor($giftWrappingFee - ($giftWrappingFee / (1 + ($this->getTaxRateByTaxConfig('tax/classes/wrapping_tax_class', $request) / 100))));
        //$taxAuthority += floor($order->getShippingInclTax() - ($order->getShippingInclTax() / (1 + ($this->getTaxRateByTaxConfig('tax/classes/shipping_tax_class', $request) / 100))));
        //$taxAuthority += floor($order->getFee() - ($order->getFee() / (1 + ($this->getTaxRateByTaxConfig('tax/classes/payment_tax_class', $request) / 100))));

        $order->setTaxForAuthority($taxAuthority);
    }

    /**
     * @param $taxClassId
     * @param $request
     * @return mixed
     */
    protected function _getRateByTaxClassId($taxClassId, $request){
        if(!isset($this->_taxClassIdToRate[$taxClassId])){

            $this->_taxClassIdToRate[$taxClassId] = 0;

            $request->setProductClassId($taxClassId);

            $this->_taxClassIdToRate[$taxClassId] = $this->_taxCalculation->getRate($request);
        }

        return $this->_taxClassIdToRate[$taxClassId];
    }

    /**
     * @param $configPath
     * @param $request
     * @return int|mixed
     */
    public function getTaxRateByTaxConfig($configPath, $request){
        $taxClassId = $this->_scopeConfig->getValue($configPath);

        if($taxClassId){
            return $this->_getRateByTaxClassId($taxClassId, $request);
        }

        return 0;
    }
}
