<?php
namespace Riki\Subscription\Model\Order;

use Riki\Subscription\Api\CreateOrderRepositoryInterface;

class CreateOrderRepository implements CreateOrderRepositoryInterface
{
    /**
     * @var $customerAddressRepositoryInterface \Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $customerAddressRepositoryInterface;
    /**
     * @var $quoteAddressInterface \Magento\Quote\Api\Data\AddressInterface
     */
    protected $quoteAddressInterface;
    /**
     * @var \Riki\Checkout\Api\Data\AddressItemRelationshipInterface
     */
    protected $addressItemRelationshipProcessor;
    /**
     * @var $addressTotalFactory \Magento\Quote\Model\Quote\Address\TotalFactory
     */
    protected $addressTotalFactory;
    /**
     * @var $shippingFeeCalculator \Riki\ShippingProvider\Api\Data\CalculateShippingFeeBasedOnAddressItemProcessorInterface
     */
    protected $shippingFeeCalculator;
    public function __construct(
        \Magento\Customer\Api\AddressRepositoryInterface $customerAddressRepositoryInterface,
        \Magento\Quote\Api\Data\AddressInterface $quoteAddressInterface,
        \Riki\Checkout\Api\Data\AddressItemRelationshipInterface $addressItemRelationshipProcessor,
        \Magento\Quote\Model\Quote\Address\TotalFactory $addressTotalFactory,
        \Riki\ShippingProvider\Api\Data\CalculateShippingFeeBasedOnAddressItemProcessorInterface $shippingFee
    )
    {
        $this->customerAddressRepositoryInterface = $customerAddressRepositoryInterface;
        $this->quoteAddressInterface = $quoteAddressInterface;
        $this->addressItemRelationshipProcessor = $addressItemRelationshipProcessor;
        $this->addressTotalFactory = $addressTotalFactory;
        $this->shippingFeeCalculator =  $shippingFee;
    }

    /**
     * @param $quote
     * @param $quoteItem
     * @param $addressId
     * @throws \Exception
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function saveQuoteItemAddress($quote,$quoteItem,$addressId){
        $totalShippingFee = 0;
        try {
            $customerAddressId = $addressId;
            $customerAddressObject = $this->customerAddressRepositoryInterface->getById($customerAddressId);
        }
        catch(\Magento\Framework\Exception\LocalizedException $exception){
            throw $exception;
        }
        $cartItemObjects = [];
        foreach ($quote->getAllItems() as $quoteItem){
            $cartItemObjects[] = $quoteItem;
        }
        $shippingFee = $this->shippingFeeCalculator->calculateShippingFeeBaseOnAddressItem($quote,$cartItemObjects);
        $totalShippingFee += $shippingFee;
        /** @var $total \Magento\Quote\Model\Quote\Address\Total */
        $total = $this->addressTotalFactory->create('\Magento\Quote\Model\Quote\Address\Total');
        $total->addTotalAmount("shipping_incl_tax",$shippingFee);
        $total->addBaseTotalAmount("shipping_incl_tax",$shippingFee);
        /* process to save quote item-address relationship */
        /** @var $quoteAddress \Magento\Quote\Model\Quote\Address */
        $quoteAddress = $this->quoteAddressInterface->importCustomerAddressData($customerAddressObject);
        $quoteAddress->setShippingMethod('riki_shipping_riki_shipping');
        $quoteAddress->setAddressType(\Riki\Sales\Helper\Address::ADDRESS_MULTI_SHIPPING_TYPE);
        $quoteAddress->setEmail($quote->getCustomerEmail());
        $quoteAddress->addTotal($total);
        $quoteAddress->unsetData('address_id');
        try {
            $quoteAddress->setQuoteId($quote->getId());
            $quoteAddress->save();
            /**
             * @TODO: do something with relationship object $cartItemAddress
             */
            /** @var $cartItemAddress \Magento\Quote\Model\Quote\Address\Item */
            $cartItemAddress = $this->addressItemRelationshipProcessor->saveAddressItemRelation(
                $quote,
                $quoteItem,
                $quoteAddress
            );
        }
        catch(\Exception $e){
            throw $e;
        }
    }

    public function saveOrderAddressItem($quote,$order){
        $this->addressItemRelationshipProcessor->saveOrderAddressItemRelation($quote,$order);
    }
}