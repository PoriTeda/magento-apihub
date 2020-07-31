<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Riki\SpotOrderApi\Model\Quote\Item;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Phrase;
use Riki\CreateProductAttributes\Model\Product\CaseDisplay;

class Repository extends \Magento\Quote\Model\Quote\Item\Repository implements \Riki\SpotOrderApi\Api\CartItemRepositoryInterface
{
    /**
     * @var \Magento\Framework\Webapi\Rest\Request
     */
    protected $_request;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * @var \Magento\Quote\Api\Data\CartItemInterface
     */
    protected $_cartItemInterface;

    /**
     * @var \Riki\SpotOrderApi\Helper\HandleMessageApi
     */
    protected $helperHandleMessage;

    /**
     * @var
     */
    protected $product;

    /**
     * @var \Riki\Subscription\Helper\Data
     */
    protected $subscriptionHelper;

    /**
     * Repository constructor.
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Quote\Api\Data\CartItemInterfaceFactory $itemDataFactory
     * @param \Magento\Framework\Webapi\Rest\Request $request
     * @param \Magento\Quote\Api\Data\CartItemInterface $cartItemInterface
     * @param \Riki\SpotOrderApi\Helper\HandleMessageApi $helperHandleMessage
     * @param \Riki\Subscription\Helper\Data $subscriptionHelper
     * @param array $cartItemProcessors
     */
    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Quote\Api\Data\CartItemInterfaceFactory $itemDataFactory,
        \Magento\Framework\Webapi\Rest\Request $requestApi,
        \Magento\Quote\Api\Data\CartItemInterface $cartItemInterface,
        \Riki\SpotOrderApi\Helper\HandleMessageApi $helperHandleMessage,
        \Riki\Subscription\Helper\Data $subscriptionHelper,
        $cartItemProcessors = []
    ) {
        parent::__construct($quoteRepository, $productRepository, $itemDataFactory, $cartItemProcessors);
        $this->_request = $requestApi;
        $this->_productRepository = $productRepository;
        $this->_cartItemInterface = $cartItemInterface;
        $this->helperHandleMessage = $helperHandleMessage;
        $this->subscriptionHelper = $subscriptionHelper;
    }

    /**
     * @param \Magento\Quote\Api\Data\CartItemInterface $cartItem
     * @return \Magento\Quote\Api\Data\CartItemInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function save(\Magento\Quote\Api\Data\CartItemInterface $cartItem)
    {
        try {
            /**
             * add param check web call spot order api
             */
            $this->_request->setParam('call_spot_order_api', 'call_spot_order_api');

            /**
             * Validate data
             */
            $this->validateDataApi($cartItem);

            /***
             * Update product quantity
             */
            $cartItem = $this->addItemId($cartItem);

            /**
             * Check quantity product pie ,case
             */
            $cartItem = $this->checkProductPiceCase($cartItem);

            /**
             * Process cart item
             */
            $result = parent::save($cartItem);

            /**
             * Return data
             */
            $returnData = $this->getReturnData($cartItem, $result);

            return $returnData;
        } catch (\Exception $e) {
            /**
             * Handel message
             */
            $arrMessage = $this->helperHandleMessage->handleMessage($e->getMessage(), $e->getFile());
            return $arrMessage;
        }
    }

    /**
     * Validate data input
     *
     * @param \Magento\Quote\Api\Data\CartItemInterface $cartItem
     * @return null
     * @throws InputException
     */
    public function validateDataApi(\Magento\Quote\Api\Data\CartItemInterface $cartItem)
    {

        $data = $this->_request->getRequestData();

        if ($cartItem->getSku() == null) {
            throw InputException::requiredField('sku');
        }

        if ($cartItem->getName() == null) {
            throw InputException::requiredField('name');
        }

        if ($cartItem->getQuoteId() == null) {
            throw InputException::requiredField('quoteId');
        }

        if (isset($data['cartItem'])) {
            //validate qty
            if (isset($data['cartItem']['qty'])) {
                if ($data['cartItem']['qty'] == null) {
                    throw InputException::requiredField('qty');
                } else if ($data['cartItem']['qty'] <= 0) {
                    throw new InputException(new Phrase(InputException::INVALID_FIELD_MIN_VALUE, ['fieldName' => 'qty', 'value' => $data['cartItem']['qty'], 'minValue' => 1]));
                }
            }

            if (isset($data['cartItem']['price'])) {
                if ($data['cartItem']['price'] == null) {
                    throw InputException::requiredField('price');
                } else if ($data['cartItem']['price'] < 0) {
                    throw new InputException(new Phrase(InputException::INVALID_FIELD_MIN_VALUE, ['fieldName' => 'price', 'value' => $data['cartItem']['price'], 'minValue' => 0]));
                }
            }
        }
        return null;
    }

    /**
     * @param \Magento\Quote\Api\Data\CartItemInterface $cartItem
     * @return bool|\Magento\Quote\Api\Data\CartItemInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function addItemId(\Magento\Quote\Api\Data\CartItemInterface $cartItem)
    {
        if (empty($cartItem)) {
            return false;
        }

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartItem->getQuoteId());
        if (empty($quote)) {
            return false;
        }

        //remove all item
        $listItems = $quote->getAllItems();
        if (is_array($listItems) && count($listItems) > 0) {
            foreach ($listItems as $subItem) {
                if ($cartItem->getSku() == $subItem->getSku()) {
                    $cartItem->setItemId($subItem->getItemId());
                    break;
                }
            }
        }
        return $cartItem;
    }

    /**
     * @param $sku
     * @return \Magento\Catalog\Api\Data\ProductInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductInfoBySku($sku)
    {
        return $this->productRepository->get($sku);
    }

    /**
     * @param \Magento\Quote\Api\Data\CartItemInterface $cartItem
     * @return \Magento\Quote\Api\Data\CartItemInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function checkProductPiceCase(\Magento\Quote\Api\Data\CartItemInterface $cartItem)
    {

        $this->product = $this->getProductInfoBySku($cartItem->getSku());
        $qtyRequest = $cartItem->getQty();

        $caseDisplay = $this->product->getData('case_display');
        $unitQty = ((int)$this->product->getData('unit_qty') > 0) ? (int)$this->product->getData('unit_qty') : 1;

        /**
         * Remove code multiply qty with unit qty
         * Not multiply qty with unitQty on case add new product (have not itemId) to quote item, because within
         * \Riki\Quote\Observer\UpdateCartItem::handleProductCase will multiple qty with unitQty
         */
        $itemId = $cartItem->getItemId();
        //only case
        /* if ($caseDisplay == 2) {
             $qtyRequest = $qtyRequest * $unitQty;
         }*/

        //set data request
        $dataCartItems = $this->_request->getRequestData();
        if (isset($dataCartItems['cartItem'])) {
            $dataCartItems['cartItem']['qty'] = $qtyRequest;
            $this->_request->setParam('cartItem', $dataCartItems['cartItem']);
        }

        $cartItem->setQty($qtyRequest);

        return $cartItem;
    }

    /**
     * @param \Magento\Quote\Api\Data\CartItemInterface $cartItem
     * @param $result
     * @return array
     */
    public function getReturnData(\Magento\Quote\Api\Data\CartItemInterface $cartItem, $result)
    {
        $caseDisplay = $this->product->getData('case_display');
        $qtyRequest  = $cartItem->getQty();
        $unitQty     = ((int)$this->product->getData('unit_qty') > 0) ? (int)$this->product->getData('unit_qty') : 1;
        //only case
        if ($caseDisplay == 2) {
            $qtyReturn = $qtyRequest * $unitQty;
        }else{
            $qtyReturn = $qtyRequest;
        }

        if (strtolower($cartItem->getPrice()) == 'null' || $cartItem->getPrice() == 0) {
            /**
             * If price = "null", the price will get latest from Magento DB with applied promotion (if any).
             */
            $price = $result->getPrice();
            if ($this->product) {
                $price = $this->subscriptionHelper->getProductPriceInProfileEditPage(
                    $this->product,
                    $qtyReturn
                );
            }
        } else {
            /**
             * If price <> "null", the price will use from this parameter. Magento need to custom a discount to make price align with Magento logic.
             */
            $price = $cartItem->getPrice();
        }

        $dataResult = [
            "item_id" => $result->getItemId(),
            "sku" => $result->getSku(),
            "qty" => $qtyReturn,
            "name" => $result->getName(),
            "price" => $price,
            "product_type" => $result->getProductType(),
            "quote_id" => $result->getQuoteId()
        ];

        return $dataResult;
    }

}