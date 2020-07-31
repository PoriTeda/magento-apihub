<?php
namespace Riki\ShippingProvider\Helper;

use Magento\Framework\Pricing\PriceCurrencyInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * Tax data
     *
     * @var \Magento\Tax\Helper\Data
     */
    protected $_taxData = null;

    /**
     * @var \Riki\Sales\Helper\Data
     */
    protected $_rikiSalesHelper;

    protected $_rikiSalesAddressHelper;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Tax\Helper\Data $taxData
     * @param \Riki\Sales\Helper\Data $rikiSalesHelper
     * @param \Riki\Sales\Helper\Address $rikiSalesAddressHelper
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Tax\Helper\Data $taxData,
        \Riki\Sales\Helper\Data $rikiSalesHelper,
        \Riki\Sales\Helper\Address $rikiSalesAddressHelper,
        \Riki\DeliveryType\Helper\Admin $deliveryTypeAdminHelper,
        PriceCurrencyInterface $priceCurrency
    ){
        $this->priceCurrency = $priceCurrency;
        $this->_taxData = $taxData;
        $this->_rikiSalesHelper = $rikiSalesHelper;
        $this->_rikiSalesAddressHelper = $rikiSalesAddressHelper;

        parent::__construct($context);
    }

    /**
     * @param $object
     * @param bool|false $isHtml
     * @return array
     * @throws \Zend_Json_Exception
     */
    public function parseShippingFeeByAddressDeliveryType($object, $isHtml = false){
        $result = [];

        $shippingFeeData = $object->getShippingFeeByAddress();

        if($shippingFeeData){
            $parsedShippingFee = \Zend_Json::decode($shippingFeeData);

            if(is_array($parsedShippingFee)){
                foreach($parsedShippingFee as $addressId    =>  $addressShippingFeeItems){

                    $addressId = (int)$addressId;

                    if(
                        !$addressId &&
                        $object->getData('is_multiple_shipping') &&
                        $object instanceof \Magento\Sales\Model\Order
                    ){
                        $addressId = $this->_rikiSalesAddressHelper->getDefaultShippingAddress($this->_rikiSalesHelper->getQuoteByOrder($object)->getCustomer());
                    }

                    $result[$addressId] = [];

                    foreach($addressShippingFeeItems as $addressShippingFeeItem){
                        foreach($addressShippingFeeItem as $deliveryType   =>  $shippingFee){
                            $result[$addressId][$deliveryType] = $this->getShippingPrice((float)$shippingFee, $object->getShippingAddress(), $object->getStore(), $isHtml);
                        }
                        break;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param \Magento\Sales\Model\Order|\Magento\Quote\Model\Quote $object
     * @return array
     * @throws \Zend_Json_Exception
     */
    public function parseShippingFee($object){
        return $this->parseShippingFeeByAddressDeliveryType($object, true);
    }

    /**
     * @param $price
     * @param $address
     * @param $store
     * @param bool|true $isHtml
     * @return float|string
     */
    public function getShippingPrice($price, $address, $store, $isHtml = true)
    {
        $priceInclTax = $this->_taxData->getShippingPrice(
            $price,
            true,
            $address,
            null,
            $store
        );

        if($isHtml){
            return $this->priceCurrency->convertAndFormat(
                $priceInclTax,
                true,
                PriceCurrencyInterface::DEFAULT_PRECISION,
                $store
            );
        }

        return $priceInclTax;
    }
}
