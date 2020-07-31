<?php

namespace Riki\Checkout\Model;

use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Config\Definition\Exception\Exception;

class AddressItemRelationship
    implements \Riki\Checkout\Api\Data\AddressItemRelationshipInterface
{

    const SALES_CONNECTION_NAME = "sales";

    /**
     * @var $cartItemAddressFactory \Magento\Quote\Model\Quote\Address\Item
     */
    protected $cartItemAddressFactory;

    /**
     * @var $logger \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var $orderAddressItemFactory \Riki\Checkout\Model\Order\Address\Item
     */
    protected $orderAddressItemFactory;

    /**
     * @var $quoteAddressCollectionFactory \Magento\Quote\Model\ResourceModel\Quote\Address\Collection
     */
    protected $quoteAddressCollectionFactory;

    /**
     * @var $orderAddressFactory \Magento\Sales\Model\Order\Address
     */
    protected $orderAddressFactory;

    /**
     * @var $toOrderAddressFactory \Magento\Quote\Model\Quote\Address\ToOrderAddress
     */
    protected $toOrderAddressFactory;

    /**
     * @var $orderItemInterfaceFactory \Magento\Sales\Api\Data\OrderItemInterface
     */
    protected $orderItemInterfaceFactory;

    /**
     * @var $resourceConnection \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;

    /**
     * AddressItemRelationship constructor.
     *
     * @param \Magento\Quote\Model\Quote\Address\ItemFactory $cartAddressItemFactory
     * @param Order\Address\ItemFactory $orderAddressItemFactory
     * @param \Magento\Quote\Model\ResourceModel\Quote\Address\CollectionFactory $quoteAddressCollectionFactory
     * @param \Magento\Sales\Model\Order\AddressFactory $orderAddressFactory
     * @param \Magento\Quote\Model\Quote\Address\ToOrderAddressFactory $toOrderAddressFactory
     * @param \Magento\Sales\Api\Data\OrderItemInterfaceFactory $orderItemInterfaceFactory
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Quote\Model\Quote\Address\ItemFactory $cartAddressItemFactory,
        \Riki\Checkout\Model\Order\Address\ItemFactory $orderAddressItemFactory,
        \Magento\Quote\Model\ResourceModel\Quote\Address\CollectionFactory $quoteAddressCollectionFactory,
        \Magento\Sales\Model\Order\AddressFactory $orderAddressFactory,
        \Magento\Quote\Model\Quote\Address\ToOrderAddressFactory $toOrderAddressFactory,
        \Magento\Sales\Api\Data\OrderItemInterfaceFactory $orderItemInterfaceFactory,
        \Psr\Log\LoggerInterface $logger
    ){
        $this->cartItemAddressFactory = $cartAddressItemFactory;
        $this->orderAddressItemFactory = $orderAddressItemFactory;
        $this->quoteAddressCollectionFactory = $quoteAddressCollectionFactory;
        $this->orderAddressFactory = $orderAddressFactory;
        $this->toOrderAddressFactory = $toOrderAddressFactory;
        $this->orderItemInterfaceFactory = $orderItemInterfaceFactory;
        $this->logger = $logger;
    }


    /**
     * Save relationship between quote item - quote item address
     *
     * @param \Magento\Quote\Api\Data\CartInterface $cart
     * @param \Magento\Quote\Api\Data\CartItemInterface $cartItem
     * @param \Magento\Quote\Api\Data\AddressInterface $addressInterface
     * @return \Magento\Quote\Model\Quote\Address\Item $cartItemAddress
     * @throws \Exception
     */
    public function saveAddressItemRelation(
        \Magento\Quote\Api\Data\CartInterface $cart,
        \Magento\Quote\Api\Data\CartItemInterface $cartItem,
        \Magento\Quote\Api\Data\AddressInterface $addressInterface
    ) {
        /** @var $cartItemAddress \Magento\Quote\Model\Quote\Address\Item */
        $cartItemAddress = $this->cartItemAddressFactory->create();
        $resource =  $cartItemAddress->getResource();
        $cartItemAddress->importQuoteItem($cartItem);
        $cartItemAddress->setAddress($addressInterface);
//        $resource->getConnection()->beginTransaction();
        try {
            $cartItemAddress->save();
//            $resource->getConnection()->commit();
            return $cartItemAddress;
        }
        catch(\Exception $e){
//            $resource->getConnection()->rollBack();
            $this->logger->critical(_("Could not save cart item-address relation ship"));
            $this->logger->debug("Cart item address data:".\Zend_Json::encode($cartItemAddress->debug()));
            throw $e;
        }
        /**
         * @TODO : implement case when return false
         */
        return false;
    }

    /**
     * Sale order address item
     *
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return bool
     * @throws \Exception
     */
    public function saveOrderAddressItemRelation(
        \Magento\Quote\Api\Data\CartInterface $quote,
        \Magento\Sales\Api\Data\OrderInterface $order
    ) {
        $quoteId = $quote->getId();
        return $this->saveOrderAddressItemRelationByQuoteId($quoteId, $order);
    }

    /**
     * convert data from quote address to order address item table
     *
     * @param $quoteId
     * @param $order
     * @return bool
     * @throws \Exception
     */
    public function saveOrderAddressItemRelationByQuoteId($quoteId, $order)
    {
        /** @var $quoteAddressCollection \Magento\Quote\Model\ResourceModel\Quote\Address\Collection */
        $quoteAddressCollection = $this->quoteAddressCollectionFactory->create();

        $quoteAddressCollection->setQuoteFilter($quoteId);

        /** @var $toOrderAddressObject \Magento\Quote\Model\Quote\Address\ToOrderAddress */
        $toOrderAddressObject = $this->toOrderAddressFactory->create();

        /*flag to store quote address after convert - to avoid convert twice or more*/
        $convertAddress = [];

        /*flag to check order address item record is exist - to avoid many record for order_address_item table*/
        $addressForItem = [];

        /** @var $quoteAddress \Magento\Quote\Model\Quote\Address */
        foreach ($quoteAddressCollection as $quoteAddress) {

            foreach ($order->getItems() as $orderItem) {

                if (in_array($orderItem->getId(), $addressForItem)) {
                    continue 1;
                }

                $quoteAddressItem = $quoteAddress->getItemByQuoteItemId($orderItem->getQuoteItemId());

                if ($quoteAddressItem) {

                    if (!isset($convertAddress[$quoteAddress->getId()])) {
                        /* try to convert quote address item to order address item */

                        /** @var $orderAddressObject \Magento\Sales\Model\Order\Address */
                        $orderAddressObject = $toOrderAddressObject->convert($quoteAddress,array(
                            "address_type" => "shipping"
                        ));

                        $orderAddressObject->setOrder($order);

                        try {
                            $orderAddressObject->save();
                            $convertAddress[$quoteAddress->getId()] = $orderAddressObject;
                        } catch (\Exception $e) {
                            $this->logger->critical(__("Could not convert from quote address to order address"));
                            $this->logger->debug(\Zend_Json::encode($quoteAddress->debug()));
                            throw $e;
                        }
                    } else {
                        $orderAddressObject = $convertAddress[$quoteAddress->getId()];
                    }

                    /*create new record for table order_address_item*/

                    /* almost done try to save order address item */

                    /** @var $orderAddressItem \Riki\Checkout\Model\Order\Address\Item */
                    $orderAddressItem = $this->orderAddressItemFactory->create();
                    $orderAddressItem->importOrderItem($orderItem);
                    $orderAddressItem->setAddress($orderAddressObject);
                    $orderAddressItem->save();

                    array_push($addressForItem, $orderItem->getId());
                }
            }
        }

        // Check if no order_address_item was saved - throw Error
        // Must have order_address_item for multiple shipping checkout
        if (count($addressForItem) == 0) {
            $this->logger->critical(
                __(
                    "[NED-6354] Could not found quote item address data for multiple address shipping - Quote ID %1",
                    $quoteId
                )
            );
            $this->logger->debug(\Zend_Json::encode($quoteAddress->debug()));
            throw new LocalizedException(__("Could not convert from quote address to order address"));
        }

        return true;
    }
}