<?php

namespace Riki\Checkout\Model;

use Psr\Log\LoggerInterface as Logger;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\InputException;

class ManageCart implements \Riki\Checkout\Api\ManageCartInterface
{
    /**
     * @var $logger \Psr\Log\LoggerInterface
     */
    protected $logger;
    /**
     * @var $customerAddressRepositoryInterface \Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $customerAddressRepositoryInterface;
    /**
     * @var $quoteAddressInterface \Magento\Quote\Api\Data\AddressInterface
     */
    protected $quoteAddressInterface;
    /**
     * Quote repository.
     *
     * @var $quoteRepository \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;
    /**
     * @var $quoteItemAddressRelationShipProcessor \Riki\Checkout\Api\Data\AddressItemRelationshipInterface
     */
    protected $quoteItemAddressRelationShipProcessor;

    /**
     * @var $addressDdateProcessor \Riki\DeliveryType\Api\Data\QuoteItemAddressDdateProcessorInterface
     */
    protected $addressDdateProcessor;

    /**
     * @var $shippingFeeCalculator \Riki\ShippingProvider\Api\Data\CalculateShippingFeeBasedOnAddressItemProcessorInterface
     */
    protected $shippingFeeCalculator;
    /**
     * @var \Riki\Sales\Helper\Data
     */
    protected $helperSale;

    protected $_rikiShippingAddress;

    /**
     * GroupItemByAddress constructor.
     *
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Customer\Api\AddressRepositoryInterface $customerRepositoryInterface
     * @param \Riki\Checkout\Api\Data\AddressItemRelationshipInterface $quoteItemAddressRelationShipProcessor
     * @param \Riki\DeliveryType\Api\Data\QuoteItemAddressDdateProcessorInterface $addressDdateProcessor
     * @param \Riki\ShippingProvider\Api\Data\CalculateShippingFeeBasedOnAddressItemProcessorInterface $shippingFeeCalculator
     * @param AddressInterface $quoteAddressInterface
     * @param Logger $loggerInterface
     * @param \Riki\Sales\Helper\Data $helperSale
     */
    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Customer\Api\AddressRepositoryInterface $customerRepositoryInterface,
        \Riki\Checkout\Api\Data\AddressItemRelationshipInterface $quoteItemAddressRelationShipProcessor,
        \Riki\DeliveryType\Api\Data\QuoteItemAddressDdateProcessorInterface $addressDdateProcessor,
        \Riki\ShippingProvider\Api\Data\CalculateShippingFeeBasedOnAddressItemProcessorInterface $shippingFeeCalculator,
        AddressInterface $quoteAddressInterface,
        Logger $loggerInterface,
        \Riki\Checkout\Model\ShippingAddress $rikiShippingAddress,
        \Riki\Sales\Helper\Data $helperSale
    ) {
        $this->logger = $loggerInterface;
        $this->customerAddressRepositoryInterface = $customerRepositoryInterface;
        $this->quoteAddressInterface = $quoteAddressInterface;
        $this->quoteRepository = $quoteRepository;
        $this->quoteItemAddressRelationShipProcessor = $quoteItemAddressRelationShipProcessor;
        $this->addressDdateProcessor = $addressDdateProcessor;
        $this->shippingFeeCalculator = $shippingFeeCalculator;
        $this->helperSale = $helperSale;
        $this->_rikiShippingAddress = $rikiShippingAddress;
    }

    /**
     * Validate quote
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @throws InputException
     * @throws NoSuchEntityException
     * @return void
     */
    protected function validateQuote(\Magento\Quote\Model\Quote $quote)
    {
        if ($quote->isVirtual()) {
            throw new NoSuchEntityException(
                __('Cart contains virtual product(s) only. Shipping address is not applicable.')
            );
        }

        if (0 == $quote->getItemsCount()) {
            throw new InputException(__('Shipping method is not applicable for empty cart'));
        }
    }

    /**
     * group item
     *
     * @param string $cartId
     * @return string[]
     */
    public function groupItemByAddress($cartId)
    {
        $quote = $this->quoteRepository->getActive($cartId);
        $this->validateQuote($quote);

        //combine item same address, applied tier price..
        $result = $this->helperSale->combineItems($quote);

        if($result){
            return $this->_rikiShippingAddress->processQuoteDataForDelivery($quote, $this->generateCartDataForResponse($quote));
        }

        return false;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @return string
     */
    public function generateCartDataForResponse($quote){
        $cartItemData = [];

        foreach($quote->getAllVisibleItems() as $quoteItem){
            if ($quoteItem->getParentItemId()) {
                continue;
            }

            $addressId = $quoteItem->getAddressId()? $quoteItem->getAddressId() : $quote->getCustomer()->getDefaultShipping();

            $cartItemData[$quoteItem->getId()] = ['address' =>  $addressId];
        }

        return ['cart'  =>  $cartItemData];
    }

}
