<?php

namespace Riki\Checkout\Model;

use Riki\Checkout\Api\ShippingAddressInterface;
use Psr\Log\LoggerInterface as Logger;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\App\State;
class ShippingAddress
    implements ShippingAddressInterface
{
    /**
     * @var State
     */
    protected $appState;

    protected $sessionCheckout;
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
     * @var $shippingFeeCalculator \Riki\ShippingProvider\Model\Carrier
     */
    protected $shippingFeeCalculator;

    /**
     * ShippingAddress constructor.
     *
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param AddressRepositoryInterface $customerRepositoryInterface
     * @param \Riki\Checkout\Api\Data\AddressItemRelationshipInterface $quoteItemAddressRelationShipProcessor
     * @param \Riki\DeliveryType\Api\Data\QuoteItemAddressDdateProcessorInterface $addressDdateProcessor
     * @param \Riki\ShippingProvider\Model\Carrier $shippingFeeCalculator
     * @param AddressInterface $quoteAddressInterface
     * @param Logger $loggerInterface
     * 
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Customer\Api\AddressRepositoryInterface $customerRepositoryInterface,
        \Riki\Checkout\Api\Data\AddressItemRelationshipInterface $quoteItemAddressRelationShipProcessor,
        \Riki\DeliveryType\Api\Data\QuoteItemAddressDdateProcessorInterface $addressDdateProcessor,
        \Riki\ShippingProvider\Model\Carrier $shippingFeeCalculator,
        AddressInterface $quoteAddressInterface,
        Logger $loggerInterface,
        \Magento\Checkout\Model\Session\Proxy $proxy,
        State $state
    ) {
        $this->logger = $loggerInterface;
        $this->customerAddressRepositoryInterface = $customerRepositoryInterface;
        $this->quoteAddressInterface = $quoteAddressInterface;
        $this->quoteRepository = $quoteRepository;
        $this->quoteItemAddressRelationShipProcessor = $quoteItemAddressRelationShipProcessor;
        $this->addressDdateProcessor = $addressDdateProcessor;
        $this->shippingFeeCalculator = $shippingFeeCalculator;
        $this->sessionCheckout = $proxy;
        $this->appState = $state;
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
     * @param string $cartId
     * @param string $itemAddressInformation
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws StateException
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return string[] $addressDdateInfo
     */

    public function saveItemAddressInformation($cartId, $itemAddressInformation)
    {
        $cartData = array();
        parse_str(urldecode($itemAddressInformation), $cartData); // @codingStandardsIgnoreLine
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);

        return $this->processQuoteDataForDelivery($quote, $cartData);
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param $cartData
     * @return string
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function processQuoteDataForDelivery($quote, $cartData)
    {
        $this->validateQuote($quote);
        $addressDdateInfo = array();
        $nonAddressDdateInfo = array();
        /* extract cart data and do save address per item  */

        foreach ($cartData["cart"] as $cartItemId => $cartItemData) {

            if (isset($cartItemData["address"]) && $customerAddressId = $cartItemData["address"]) // Customer already has addresses
            {
                if (!isset($addressDdateInfo[$customerAddressId]["cartItems"])
                    && empty($addressDdateInfo[$customerAddressId]["cartItems"])
                ) {
                    $addressDdateInfo[$customerAddressId]["cartItems"] = array();
                    $addressDdateInfo[$customerAddressId]["cartItems"][] = array(
                        "id" => $cartItemId,
                        'point' => 1/** @TODO: implement get earn point for cart item */
                    );
                } else {
                    $addressDdateInfo[$customerAddressId]["cartItems"][] = array(
                        "id" => $cartItemId,
                        'point' => 1/** @TODO: implement get earn point for cart item */
                    );
                }
            } else { // case virtual product cases
                /**
                 * @TODO : implement it later
                 */

                try {
                    /** @var $cartItemObject \Magento\Quote\Model\Quote\Item */
                    $cartItemObject = $quote->getItemById($cartItemId);
                } catch (\Exception $e) {
                    $this->logger->debug($cartItemId);
                    $this->logger->critical(__("Could not load cart item"));
                    throw $e;
                }
                if (isset($nonAddressDdateInfo["cartItems"])) {
                    /* initialization cart item array */
                    $nonAddressDdateInfo["cartItems"] = array();
                    $nonAddressDdateInfo["cartItems"][] = array(
                        "id" => $cartItemId,
                        "sku"   =>  $cartItemObject->getSku(),
                        "product_id" => $cartItemObject->getProductId(),
                        "name" => $cartItemObject->getName(),
                        "price_incl_tax" => $cartItemObject->getPriceInclTax(),
                        "price_excl_tax" => $cartItemObject->getPrice(),
                        "row_subtotal_incl_tax" => $cartItemObject->getRowTotalInclTax(),
                        "row_subtotal_excl_tax" => $cartItemObject->getRowTotal(),
                        "delivery_type" => $cartItemObject->getDeliveryType(),
                        "free_shipping" => $cartItemObject->getFreeShipping(),
                        "gift_wrapping" => $cartItemObject->getProduct()->getGiftWrapping(),
                        "gw_id" => $cartItemObject->getGwId(),
                        "qty" => $cartItemObject->getQty(),
                        "qty_case" => $cartItemObject->getQty() / ((int)($cartItemObject->getUnitQty()) ? (int)($cartItemObject->getUnitQty()) : 1),
                        "unit_case" => $cartItemObject->getUnitCase(),

                        "unit_case_ea" => __($cartItemObject->getUnitCase()),

                        "request_path" => $cartItemObject->getProduct()->getRequestPath(),
                        "item_id" => $cartItemObject->getItemId(),
                        "point" => 1/** @TODO: implement get earn point for cart item */
                    );
                } else {
                    $nonAddressDdateInfo["cartItems"][] = array(
                        "id" => $cartItemId,
                        "sku"   =>  $cartItemObject->getSku(),
                        "product_id" => $cartItemObject->getProductId(),
                        "name" => $cartItemObject->getName(),
                        "price_incl_tax" => $cartItemObject->getPriceInclTax(),
                        "price_excl_tax" => $cartItemObject->getPrice(),
                        "row_subtotal_incl_tax" => $cartItemObject->getRowTotalInclTax(),
                        "row_subtotal_excl_tax" => $cartItemObject->getRowTotal(),
                        "delivery_type" => $cartItemObject->getDeliveryType(),
                        "free_shipping" => $cartItemObject->getFreeShipping(),
                        "gift_wrapping" => $cartItemObject->getProduct()->getGiftWrapping(),
                        "gift_wrapping_available" => $cartItemObject->getProduct()->getGiftWrappingAvailable(),
                        "gw_id" => $cartItemObject->getGwId(),
                        "qty" => $cartItemObject->getQty(),
                        "qty_case" => $cartItemObject->getQty() / ((int)($cartItemObject->getUnitQty()) ? ((int)$cartItemObject->getUnitQty()) : 1),
                        "unit_case" => $cartItemObject->getUnitCase(),
                        "unit_case_ea" => __($cartItemObject->getUnitCase()),
                        "request_path" => $cartItemObject->getProduct()->getRequestPath(),
                        "item_id" => $cartItemObject->getItemId(),
                        "point" => 1/** @TODO: implement get earn point for cart item */
                    );
                }
            }
        }
        // we're going to delete all quote address before import new
        $this->cleanShippingAddress($quote);

        /* process before sending the result */
        $processedAddressDataInfo = array();

        $this->shippingFeeCalculator->calculateFeeForEachAddress($quote);

        foreach ($addressDdateInfo as $addressId => $data) {

            $processedAddressDataInfo["addressDdateInfo"][] = $this->generateCartItemData($quote, $addressId, $data["cartItems"]);
        }

        if (count($nonAddressDdateInfo)) {
            $processedAddressDataInfo["nonAddressDdateInfo"] = $nonAddressDdateInfo;
        }
        $isAdmin = $this->appState->getAreaCode() == \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE;
        if (!$isAdmin) {
            $quote->setData('shipping_fee_by_address', $this->sessionCheckout->getData('shipping_fee_by_address'))->save();
        }

        return \Zend_Json::encode($processedAddressDataInfo);
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param $addressId
     * @param array $cartItems
     * @return array
     * @throws \Exception
     */
    public function generateCartItemData(\Magento\Quote\Model\Quote $quote, $addressId, array $cartItems)
    {

        $this->validateAddressRequestData($quote, $addressId, $cartItems);

        $cartItemsObject = array();
        $processedAddressDataInfoItem = array("address_id" => $addressId);

        $customerAddressData = $this->customerAddressRepositoryInterface->getById($addressId);

        /* building quote address */
        $quoteAddress = $this->quoteAddressInterface->importCustomerAddressData($customerAddressData);
        $quoteAddress->setShippingMethod('riki_shipping_riki_shipping');
        $quoteAddress->unsetData('address_id');
        $quoteAddress->setQuoteId($quote->getId());
        $quoteAddress->save();

        $this->quoteAddressInterface->setQuote($quote);

        foreach ($cartItems as $cartItem) {
            try {
                /** @var $cartItemObject \Magento\Quote\Model\Quote\Item */
                $cartItemObject = $quote->getItemById($cartItem["id"]);
                if(!$cartItemObject) {
                    continue;
                }

                $processedAddressDataInfoItem["cartItems"][] = array(
                    "name" => $cartItemObject->getName(),
                    "sku"   =>  $cartItemObject->getSku(),
                    "product_id" => $cartItemObject->getProductId(),
                    "price_incl_tax" => $cartItemObject->getPriceInclTax(),
                    "price_excl_tax" => $cartItemObject->getPrice(),
                    "row_subtotal_incl_tax" => $cartItemObject->getRowTotalInclTax(),
                    "row_subtotal_excl_tax" => $cartItemObject->getRowTotal(),
                    "delivery_type" => $cartItemObject->getDeliveryType(),
                    "free_shipping" => $cartItemObject->getFreeShipping(),
                    "gift_wrapping" => $cartItemObject->getProduct()->getGiftWrapping(),
                    "gift_wrapping_available" => $cartItemObject->getProduct()->getGiftWrappingAvailable(),
                    "gw_id" => $cartItemObject->getGwId(),
                    "qty" => $cartItemObject->getQty(),
                    "qty_case" => $cartItemObject->getQty() / (((int)$cartItemObject->getUnitQty()) ? ((int)$cartItemObject->getUnitQty()) : 1),
                    "unit_case" => $cartItemObject->getUnitCase(),
                    "unit_case_ea" => __($cartItemObject->getUnitCase()),
                    "request_path" => $cartItemObject->getProduct()->getRequestPath(),
                    "item_id" => $cartItemObject->getItemId(),
                    "point" => 1
                );
                /* save customer address id */

                try {
                    //set address id for quote item , re-caculator shipping fee
                    $cartItemObject->setAddressId($addressId);
                    $cartItemObject->save();

                    /* save quote address relation ship */
                    $this->quoteItemAddressRelationShipProcessor->saveAddressItemRelation(
                        $quote,
                        $cartItemObject,
                        $quoteAddress
                    );
                } catch (\Exception $e) {
                    throw new \Magento\Framework\Exception\LocalizedException(__("Unable to save address info for quote item"));
                }
                $processedAddressDataInfoItem["cartItemIds"][] = $cartItemObject->getId();
            } catch (\Exception $e) {
                $this->logger->debug($cartItem["id"]);
                $this->logger->critical(__("Could not load cart item"));
                throw $e;
            }
            $cartItemsObject[] = $cartItemObject;
        }
        /* calculate ddate information for this item-address data */
        $ddateInfo = $this->addressDdateProcessor->calDeliveryDateFollowAddressItem(
            $customerAddressData, $quote, $cartItemsObject);
        $apartment = null;
        if ($customerAddressData->getCustomAttribute('apartment') != null) {
            $apartment = $customerAddressData->getCustomAttribute('apartment')->getValue();
        } else {
            $apartment = '';
        }

        $rikiNickName = null;
        if ($customerAddressData->getCustomAttribute('riki_nickname') != null) {
            $rikiNickName = $customerAddressData->getCustomAttribute('riki_nickname')->getValue();
        } else {
            $rikiNickName = '';
        }

        $firstnamekana = null;
        if ($customerAddressData->getCustomAttribute('firstnamekana') != null) {
            $firstnamekana = $customerAddressData->getCustomAttribute('firstnamekana')->getValue();
        } else {
            $firstnamekana = '';
        }

        $lastnamekana = null;
        if ($customerAddressData->getCustomAttribute('lastnamekana') != null) {
            $lastnamekana = $customerAddressData->getCustomAttribute('lastnamekana')->getValue();
        } else {
            $lastnamekana = '';
        }

        $rikiTypeAddress = null;
        if ($customerAddressData->getCustomAttribute('riki_type_address') != null) {
            $rikiTypeAddress = $customerAddressData->getCustomAttribute('riki_type_address')->getValue();
        } else {
            $rikiTypeAddress = '';
        }

        $processedAddressDataInfoItem["addressData"] = array(
            'firstname' => $customerAddressData->getFirstname(),
            'lastname' => $customerAddressData->getLastname(),
            'street' => $customerAddressData->getStreet(),
            'city' => $customerAddressData->getCity(),
            'region' => $customerAddressData->getRegion()->getRegion(),
            'postcode' => $customerAddressData->getPostcode(),
            'countryId' => $customerAddressData->getCountryId(),
            'telephone' => $customerAddressData->getTelephone(),
            'riki_nickname' => $rikiNickName,
            'apartment' => $apartment,
            'firstname_kana' => $firstnamekana,
            'lastname_kana' => $lastnamekana,
            'riki_type_address' => $rikiTypeAddress
        );

        $calculatedFeeForEachAddress = (array)$quote->getData('calculatedFeeForEachAddress');

        /* calcalte shipping fee base on cart items */
        $totalShippingFee = 0;
        $dDateShippingFee = isset($calculatedFeeForEachAddress[$addressId])
            ? current($calculatedFeeForEachAddress[$addressId])
            : [];
        foreach ($ddateInfo as $k => $dDate) {
            if (!isset($dDateShippingFee[$dDate['code']])) {
                continue;
            }
            $ddateInfo[$k]['total_shipping_fee'] = isset($ddateInfo[$k]['total_shipping_fee'])
                ? $ddateInfo[$k]['total_shipping_fee'] + $dDateShippingFee[$dDate['code']]
                : $dDateShippingFee[$dDate['code']];

            if (isset($dDateShippingFee[$dDate['code']]) && is_numeric($dDateShippingFee[$dDate['code']])) {
                $totalShippingFee += $dDateShippingFee[$dDate['code']];
            }
        }

        $processedAddressDataInfoItem["ddate_info"] = $ddateInfo;

        return $processedAddressDataInfoItem;
    }

    /**
     * Clean shipping address
     *
     * @param $quote
     *
     * @throws \Exception
     */
    protected function cleanShippingAddress($quote)
    {
        /** @var  $quoteAddressObject \Magento\Quote\Model\Quote\Address */
        foreach ($quote->getAddressesCollection() as $quoteAddressObject) {
            try {
                if (!$quoteAddressObject->getAddressType()) {
                    $quoteAddressObject->delete();
                }
            } catch (\Exception $exception) {
                $this->logger->critical(__("Could not clean quote address for quote id {$quote->getId()}"));
                throw $exception;
            }
        }
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param $addressId
     * @param array $cartItemRequestData
     * @return $this
     */
    public function validateAddressRequestData(\Magento\Quote\Model\Quote $quote, $addressId, array $cartItemRequestData)
    {
        try {
            $this->customerAddressRepositoryInterface->getById($addressId);
        } catch (\Exception $e) {
            $this->logger->debug(\Zend_Json::encode($cartItemRequestData));
            throw new \LogicException(__('Address request is invalid.'));
        }

        return $this;
    }
}
