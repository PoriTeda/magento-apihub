<?php

namespace Riki\Checkout\Model;

use Magento\Framework\DataObject;
use Riki\Checkout\Api\DeliveryDateMethodInterface;
use Psr\Log\LoggerInterface as Logger;
use Riki\DeliveryType\Model\Delitype as Dtype;
use Magento\Framework\Exception\InputException;

class DeliveryDateMethod implements DeliveryDateMethodInterface
{
    /**
     * @var $quoteRepository \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;
    /**
     * @var $customerAddressRepositoryInterface \Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $customerAddressRepositoryInterface;
    /**
     * @var $addressItemRelationshipProcess \Riki\Checkout\Api\Data\AddressItemRelationshipInterface
     */
    protected $addressItemRelationshipProcessor;
    /**
     * @var $quoteAddressInterface \Magento\Quote\Api\Data\AddressInterface
     */
    protected $quoteAddressInterface;
    /**
     * @var $addressTotalFactory \Magento\Quote\Model\Quote\Address\TotalFactory
     */
    protected $addressTotalFactory;
    /**
     * @var \Magento\Quote\Api\PaymentMethodManagementInterface
     */
    protected $paymentMethodManagement;
    /**
     * @var \Magento\Quote\Api\CartTotalRepositoryInterface
     */
    protected $cartTotalsRepository;
    /**
     * @var $logger \Psr\Log\LoggerInterface
     */
    protected $logger;
    /**
     * @var \Riki\DeliveryType\Model\DeliveryDate $deliveryDateModel
     */
    protected $deliveryDateModel;


    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Customer\Api\AddressRepositoryInterface $customerAddressRepositoryInterface,
        \Riki\Checkout\Api\Data\AddressItemRelationshipInterface $addressItemRelationshipProcessor,
        \Riki\DeliveryType\Model\DeliveryDate $deliveryDateModel,
        \Magento\Quote\Model\Quote\Address\TotalFactory $addressTotalFactory,
        \Magento\Quote\Api\Data\AddressInterface $quoteAddress,
        \Magento\Quote\Api\PaymentMethodManagementInterface $paymentMethodManagementInterface,
        \Magento\Quote\Api\CartTotalRepositoryInterface $cartTotalRepositoryInterface,
        Logger $loggerInterface
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->customerAddressRepositoryInterface = $customerAddressRepositoryInterface;
        $this->logger = $loggerInterface;
        $this->addressItemRelationshipProcessor = $addressItemRelationshipProcessor;
        $this->quoteAddressInterface = $quoteAddress;
        $this->addressTotalFactory = $addressTotalFactory;
        $this->paymentMethodManagement = $paymentMethodManagementInterface;
        $this->cartTotalsRepository = $cartTotalRepositoryInterface;
        $this->deliveryDateModel = $deliveryDateModel;
    }

    /**
     * Validate quote
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @return void
     */
    protected function validateQuote(\Magento\Quote\Model\Quote $quote)
    {
        if ($quote->isVirtual()) {
            throw new \Magento\Framework\Exception\NoSuchEntityException(
                __('Cart contains virtual product(s) only. Shipping address is not applicable.')
            );
        }

        if (0 == $quote->getItemsCount()) {
            throw new InputException(__('Shipping method is not applicable for empty cart'));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function saveDeliveryInformation($cartId, $customerAddressInfo)
    {
        if ($customerAddressInfo == null || empty($customerAddressInfo)) {
            throw new InputException(__("Customer info not found"));
        }
        /** @var \Magento\Quote\Model\Quote $quote */
        try {
            $quote = $this->quoteRepository->getActive($cartId);
            $this->validateQuote($quote);
        } catch (\Exception $exception) {
            $this->logger->critical("Quote not found:" . $exception->getMessage());
            $this->logger->debug(\Zend_Json::encode(array($cartId, $customerAddressInfo)));
            throw $exception;
        }

        /* set quote to multi shipping mode */
        $quote->setIsMultiShipping(1);

        foreach ($customerAddressInfo as $encodeInputString) {
            $infoData = array();
            parse_str(urldecode($encodeInputString), $infoData); // @codingStandardsIgnoreLine
            $cartItemIds = $infoData["cart_items"];
            $ddateChilled = $dtimeChilled = $ddateCosmetic = $dtimeCosmetic = $ddateCold = $dtimeCold = $ddateCoolNormalDm = $dtimeCoolNormalDm = false;
            if (isset($infoData["delivery_date"]) && !empty($infoData["delivery_date"])) {
                //get list Delivery date on each address
                $listDeliveryDate = $infoData["delivery_date"];
                if (isset($listDeliveryDate)) {
                    foreach ($listDeliveryDate as $dd) {
                        if (isset($dd['deliveryName']) && $dd['deliveryName'] == Dtype::CHILLED) {
                            if (isset($dd['deliveryDate'])) {
                                $ddateChilled = $dd['deliveryDate'];
                            }
                            if (isset($dd['deliveryTime'])
                                && \Zend_Validate::is($dd['deliveryTime'], "NotEmpty")
                            ) {
                                $dtimeChilled = $this->deliveryDateModel->getTimeSlotInfo($dd['deliveryTime']); // return false when not found
                            } else {
                                $dtimeChilled = false;
                            }
                        } else if (isset($dd['deliveryName']) && $dd['deliveryName'] == Dtype::COSMETIC) {
                            if (isset($dd['deliveryDate'])) {
                                $ddateCosmetic = $dd['deliveryDate'];
                            }
                            if (isset($dd['deliveryTime'])
                                && \Zend_Validate::is($dd['deliveryTime'], "NotEmpty")
                            ) {
                                $dtimeCosmetic = $this->deliveryDateModel->getTimeSlotInfo($dd['deliveryTime']); // return false when not found
                            } else {
                                $dtimeCosmetic = false;
                            }
                        } else if (isset($dd['deliveryName']) && $dd['deliveryName'] == Dtype::COLD) {
                            if (isset($dd['deliveryDate'])) {
                                $ddateCold = $dd['deliveryDate'];
                            }
                            if (isset($dd['deliveryTime'])
                                && \Zend_Validate::is($dd['deliveryTime'], "NotEmpty")
                            ) {
                                $dtimeCold = $this->deliveryDateModel->getTimeSlotInfo($dd['deliveryTime']); // return false when not found
                            } else {
                                $dtimeCold = false;
                            }
                        } else {
                            if (isset($dd['deliveryDate'])) {
                                $ddateCoolNormalDm = $dd['deliveryDate'];
                            }
                            if (isset($dd['deliveryTime'])
                                && \Zend_Validate::is($dd['deliveryTime'], "NotEmpty")
                            ) {
                                $dtimeCoolNormalDm = $this->deliveryDateModel->getTimeSlotInfo($dd['deliveryTime']);
                            } else {
                                $dtimeCoolNormalDm = false;
                            }
                        }
                    }
                }
            }
            // save delivery date for item
            $this->_saveDDToQuoteItem(
                $quote, $cartItemIds,
                $ddateChilled, $dtimeChilled,
                $ddateCosmetic, $dtimeCosmetic,
                $ddateCold, $dtimeCold,
                $ddateCoolNormalDm, $dtimeCoolNormalDm
            );
        }

        return ['redirect'  =>  1];
    }

    /**
     * Save Delivery date to order item
     *
     * @param $orderItems
     * @param $ddateChilled
     * @param $dtimeChilled
     * @param $ddateCosmetic
     * @param $dtimeCosmetic
     * @param $ddateCold
     * @param $dtimeCold
     * @param $ddateCoolNormalDm
     * @param $dtimeCoolNormalDm
     *
     * @return $this
     */
    private function _saveDDToQuoteItem(
        $quote, $cartItemIds,
        $ddateChilled, $dtimeChilled,
        $ddateCosmetic, $dtimeCosmetic,
        $ddateCold, $dtimeCold,
        $ddateCoolNormalDm, $dtimeCoolNormalDm
    ) {
        /* try to load cart item object */
        foreach ($cartItemIds as $cartItemId) {
            $cartItemObject = $quote->getItemById($cartItemId);
            if(!$cartItemObject) {
                continue;
            }
            //save delivery date for quote item filter by address id and delivery type
            if ($cartItemObject->getData("delivery_type") == Dtype::CHILLED) {
                $this->_chilled($cartItemObject, $ddateChilled, $dtimeChilled);
            } else if ($cartItemObject->getData("delivery_type") == Dtype::COSMETIC) {
                $this->_cosmetic($cartItemObject, $ddateCosmetic, $dtimeCosmetic);
            } else if ($cartItemObject->getData("delivery_type") == Dtype::COLD) {
                $this->_cold($cartItemObject, $ddateCold, $dtimeCold);
            } else {
                $this->_itemCoolNormalDm($cartItemObject, $ddateCoolNormalDm, $dtimeCoolNormalDm);
            }
        }
    }


    /**
     * Save Delivery Data to Chilled
     *
     * @param $item
     * @param $ddateChilled
     * @param $dtimeChilled
     */
    private function _chilled($item, $ddateChilled, $dtimeChilled)
    {
        if ($ddateChilled) {
            $item->setData("delivery_date", $ddateChilled);
        }
        if ($dtimeChilled && $dtimeChilled instanceof \Magento\Framework\DataObject) {
            $item->addData(array(
                "delivery_time" => $dtimeChilled->getData("slot_name"),
                "delivery_timeslot_id" => $dtimeChilled->getData("id"),
                "delivery_timeslot_from" => $dtimeChilled->getData("from"),
                "delivery_timeslot_to" => $dtimeChilled->getData("to")
            ));
        }
        try {
            $item->save();
        } catch (\Exception $e) {
            $this->logger->critical($e);
            throw new InputException(__('Something went wrong while save Delivery Date.'));
        }
    }
    /**
     * Save Delivery Data to Cosmetic
     *
     * @param $item
     * @param $ddateCosmetic
     * @param $dtimeCosmetic
     */
    private function _cosmetic($item, $ddateCosmetic, $dtimeCosmetic)
    {
        if ($ddateCosmetic) {
            $item->setData("delivery_date", $ddateCosmetic);
        }
        if ($dtimeCosmetic && $dtimeCosmetic instanceof \Magento\Framework\DataObject) {
            $item->addData(array(
                "delivery_time" => $dtimeCosmetic->getData('slot_name'),
                "delivery_timeslot_id" => $dtimeCosmetic->getData("id"),
                "delivery_timeslot_from" => $dtimeCosmetic->getData("from"),
                "delivery_timeslot_to" => $dtimeCosmetic->getData("to")
            ));
        }
        try {
            $item->save();
        } catch (\Exception $e) {
            $this->logger->critical($e);
            throw new InputException(__('Something went wrong while save Delivery Date.'));
        }
    }

    /**
     * Save Delivery Data to Cold
     *
     * @param $item
     * @param $ddateCold
     * @param $dtimeCold
     */
    private function _cold($item, $ddateCold, $dtimeCold)
    {
        if ($ddateCold) {
            $item->setData("delivery_date", $ddateCold);
        }
        if ($dtimeCold && $dtimeCold instanceof \Magento\Framework\DataObject) {
            $item->addData(array(
                "delivery_time" => $dtimeCold->getData('slot_name'),
                "delivery_timeslot_id" => $dtimeCold->getData("id"),
                "delivery_timeslot_from" => $dtimeCold->getData("from"),
                "delivery_timeslot_to" => $dtimeCold->getData("to")
            ));
        }
        try {
            $item->save();
        } catch (\Exception $e) {
            $this->logger->critical($e);
            throw new InputException(__('Something went wrong while save Delivery Date.'));
        }
    }

    /**
     * Save Delivery Data to Cool Normal Direct mail
     *
     * @param $item
     * @param $ddateCoolNormalDm
     * @param $dtimeCoolNormalDm
     */
    private function _itemCoolNormalDm($item, $ddateCoolNormalDm, $dtimeCoolNormalDm)
    {
        if ($ddateCoolNormalDm) {
            $item->setData('delivery_date', $ddateCoolNormalDm);
        }
        if ($dtimeCoolNormalDm && $dtimeCoolNormalDm instanceof \Magento\Framework\DataObject) {
            $item->addData(array(
                "delivery_time" => $dtimeCoolNormalDm->getData('slot_name'),
                "delivery_timeslot_id" => $dtimeCoolNormalDm->getData("id"),
                "delivery_timeslot_from" => $dtimeCoolNormalDm->getData("from"),
                "delivery_timeslot_to" => $dtimeCoolNormalDm->getData("to")
            ));
        }
        if ($ddateCoolNormalDm || $dtimeCoolNormalDm) {
            try {
                $item->save();
            } catch (\Exception $e) {
                $this->logger->critical($e);
                throw new InputException(__('Something went wrong while save Delivery Date.'));
            }
        }
    }
}