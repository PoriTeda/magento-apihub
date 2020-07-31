<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\MachineApi\Model\Quote\Item;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class Repository implements \Riki\MachineApi\Api\CartItemRepositoryInterface
{
    /**
     * Quote repository.
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * Product repository.
     *
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Quote\Api\Data\CartItemInterfaceFactory
     */
    protected $itemDataFactory;

    /**
     * @var CartItemProcessorInterface[]
     */
    protected $cartItemProcessors;

    /**
     * @var \Wyomind\AdvancedInventory\Model\Stock
     */
    protected $_collectionStockWyomind;

    /**
     * @var \Magento\Framework\Webapi\Rest\Request
     */
    protected $_request;
    /**
     * @var \Magento\Quote\Model\Quote\Item
     */
    protected $_quoteItem ;

    protected $_qtyRequest;
    /**
     * @var \Riki\MachineApi\Helper\Data
     */
    protected $machineHelper;

    /**
     * @var \Riki\Catalog\Model\StockState
     */
    protected $stockState;

    /**
     * Repository constructor.
     *
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Quote\Api\Data\CartItemInterfaceFactory $itemDataFactory
     * @param \Wyomind\AdvancedInventory\Model\Stock $collectionStockWyomind
     * @param \Magento\Framework\Webapi\Rest\Request $request
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @param \Riki\MachineApi\Helper\Data $machineHelper
     * @param \Riki\Catalog\Model\StockState $stockState
     * @param array $cartItemProcessors
     */
    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Quote\Api\Data\CartItemInterfaceFactory $itemDataFactory,
        \Wyomind\AdvancedInventory\Model\Stock  $collectionStockWyomind,
        \Magento\Framework\Webapi\Rest\Request $request,
        \Magento\Quote\Model\Quote\Item $quoteItem,
        \Riki\MachineApi\Helper\Data $machineHelper,
        \Riki\Catalog\Model\StockState $stockState,
        array $cartItemProcessors = []
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->productRepository = $productRepository;
        $this->itemDataFactory = $itemDataFactory;
        $this->cartItemProcessors = $cartItemProcessors;
        $this->_collectionStockWyomind   = $collectionStockWyomind;
        $this->_request = $request;
        $this->_quoteItem =$quoteItem;
        $this->machineHelper = $machineHelper;
        $this->stockState = $stockState;
    }

    /**
     * {@inheritdoc}
     */
    public function getList($cartId)
    {
        $output = [];
        /** @var  \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);

        /** @var  \Magento\Quote\Model\Quote\Item  $item */
        foreach ($quote->getAllItems() as $item) {
            if (!$item->isDeleted() && !$item->getParentItemId()) {
                $item = $this->addProductOptions($item->getProductType(), $item);
                $output[] = $this->applyCustomOptions($item);
            }
        }
        return $output;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function save(\Magento\Quote\Api\Data\CartItemInterface $cartItem)
    {
        $this->validateDataApi($cartItem);

        $qty = $cartItem->getQty();

        if (!is_numeric($qty) || $qty <= 0) {
            throw InputException::invalidFieldValue('qty', $qty);
        }
        $this->_qtyRequest = $qty;
        $cartId = $cartItem->getQuoteId();
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        $product = $this->productRepository->get($cartItem->getSku());
        if (!$product->getId() || !$product->isSaleable()) {
            throw new LocalizedException(__('The product is not eligible to order'));
        }

        $qty     = $this->checkProductPiceCase($product,$qty);
        $cartItem->setQty($qty);

        /*check product stock*/
        $canAssigned = $this->stockState->canAssigned(
            $product,
            $qty,
            [$this->machineHelper->getMachineDefaultPlace()]
        );

        /*no back order, out of stock*/
        if (!$canAssigned) {
            throw new LocalizedException(__('We don\'t have as many quantity as you requested'));
        }

        //get data request
        $dataRequest = $this->_request->getRequestData();
        $priceCustom = $cartItem->getPrice();
        if(isset($dataRequest['cartItem']) && isset($dataRequest['cartItem']['price'])){
            $priceCustom = $dataRequest['cartItem']['price'];
            $addToCartItem[$cartItem->getSku()] = $cartItem->getQty();
            $this->_request->setParam('data_machine_api',json_encode($addToCartItem));
        }
        
        try {
            //remove all item
            $listItems = $quote->getAllItems();
            if (is_array($listItems) && !empty($listItems)) {
                foreach ($listItems as $subItem) {
                    if ($cartItem->getSku() == $subItem->getSku()) {
                        $subItem->isDeleted(true);
                    } else {
                        $subItem->isDeleted(false);
                    }
                }
            }

            /** add item to shopping cart */
            $productType = $product->getTypeId();
            /** @var  \Magento\Quote\Model\Quote\Item|string $cartItem */
            $cartItem = $quote->addProduct($product, $this->getBuyRequest($productType, $cartItem));

            if (is_string($cartItem)) {
                throw new \Magento\Framework\Exception\LocalizedException(__($cartItem));
            }

            $this->quoteRepository->save($quote->collectTotals());
        } catch (\Exception $e) {
            if ($e instanceof NoSuchEntityException || $e instanceof LocalizedException) {
                throw $e;
            }
            throw new CouldNotSaveException(__('Could not save quote'));
        }

        $itemId = $cartItem->getId();
        foreach ($quote->getAllItems() as $quoteItem) {
            if ($itemId == $quoteItem->getId()) {
                $cartItem = $this->addProductOptions($productType, $quoteItem);

                //set price client request
                $cartItem->setPrice(trim($priceCustom));
                $cartItem->setQty($this->_qtyRequest);

                return $this->applyCustomOptions($cartItem);
            }
        }

        throw new CouldNotSaveException(__('Could not save quote'));
    }

    /**
     * @param string $productType
     * @param \Magento\Quote\Api\Data\CartItemInterface $cartItem
     * @return \Magento\Framework\DataObject|float
     */
    protected function getBuyRequest(
        $productType,
        \Magento\Quote\Api\Data\CartItemInterface $cartItem
    ) {
        $params = (isset($this->cartItemProcessors[$productType]))
            ? $this->cartItemProcessors[$productType]->convertToBuyRequest($cartItem)
            : null;
        $params = ($params === null) ? $cartItem->getQty() : $params->setQty($cartItem->getQty());
        return $this->addCustomOptionsToBuyRequest($cartItem, $params);
    }

    /**
     * Add to buy request custom options
     *
     * @param \Magento\Quote\Api\Data\CartItemInterface $cartItem
     * @param \Magento\Framework\DataObject|float $params
     * @return \Magento\Framework\DataObject|float
     */
    protected function addCustomOptionsToBuyRequest(
        \Magento\Quote\Api\Data\CartItemInterface $cartItem,
        $params
    ) {
        if (isset($this->cartItemProcessors['custom_options'])) {
            $buyRequestUpdate = $this->cartItemProcessors['custom_options']->convertToBuyRequest($cartItem);
            if (!$buyRequestUpdate) {
                return $params;
            }
            if ($params instanceof \Magento\Framework\DataObject) {
                $buyRequestUpdate->addData($params->getData());
            } else if (is_numeric($params)) {
                $buyRequestUpdate->setData('qty', $params);
            }
            return $buyRequestUpdate;
        }
        return $params;
    }

    /**
     * @param \Magento\Quote\Api\Data\CartItemInterface $cartItem
     * @return \Magento\Quote\Api\Data\CartItemInterface
     */

    protected function applyCustomOptions(\Magento\Quote\Api\Data\CartItemInterface $cartItem)
    {
        if (isset($this->cartItemProcessors['custom_options'])) {
            $this->cartItemProcessors['custom_options']->processOptions($cartItem);
        }
        return $cartItem;
    }

    /**
     * @param string $productType
     * @param \Magento\Quote\Api\Data\CartItemInterface $cartItem
     * @return  \Magento\Quote\Api\Data\CartItemInterface
     */
    protected function addProductOptions(
        $productType,
        \Magento\Quote\Api\Data\CartItemInterface $cartItem
    ) {
        $cartItem = (isset($this->cartItemProcessors[$productType]))
            ? $this->cartItemProcessors[$productType]->processOptions($cartItem)
            : $cartItem;
        return $cartItem;
    }

    /**
     * Validate data input
     *
     * @param \Magento\Quote\Api\Data\CartItemInterface $cartItem
     * @return null
     * @throws InputException
     */
    public function validateDataApi(\Magento\Quote\Api\Data\CartItemInterface $cartItem){
        $message=array();
        $data = $this->_request->getRequestData();

        //add param check web call api
        $this->_request->setParam('call_machine_api','call_machine_api');

        if (!isset($data['cartId']) || $data['cartId'] != $cartItem->getQuoteId())
        {
            throw new InputException(new Phrase(InputException::INVALID_FIELD_VALUE,
                    ['fieldName' => 'cartID', 'value' => $data['cartId']])
            );
        }

        if($cartItem->getSku() ==null){
            throw InputException::requiredField('sku');
        }

        if($cartItem->getName() ==null){
            throw InputException::requiredField('name');
        }

        if($cartItem->getQuoteId() ==null){
            throw InputException::requiredField('quoteId');
        }

        if(isset($data['cartItem'])){
            //validate qyty
            if(isset($data['cartItem']['qty'])){
                if($data['cartItem']['qty'] ==null ){
                    throw InputException::requiredField('qty');
                }else if($data['cartItem']['qty']<=0){
                    throw new InputException(new Phrase(InputException::INVALID_FIELD_MIN_VALUE, ['fieldName' => 'qty', 'value' =>$data['cartItem']['qty'] , 'minValue' => 1]));
                }
            }

            if(isset($data['cartItem']['price'])){
                if($data['cartItem']['price'] === null){
                    throw InputException::requiredField('price');
                }else if($data['cartItem']['price']<0){
                    throw new InputException(new Phrase(InputException::INVALID_FIELD_MIN_VALUE, ['fieldName' => 'price', 'value' =>$data['cartItem']['price'], 'minValue' => 0]));
                }
            }
        }
        return null;
    }

    /**
     * @param $product
     * @param $qtyRequest
     * @return int
     */
    public function checkProductPiceCase($product,$qtyRequest) {
        $caseDisplay = $product->getData('case_display');
        $unitQty     = ((int)$product->getData('unit_qty')>0) ? (int)$product->getData('unit_qty') : 1;
        //only case
        if ($caseDisplay==2) {
            $qtyRequest = $qtyRequest * $unitQty;
        }

        //set data request
        $dataCartItems = $this->_request->getRequestData();
        if (isset($dataCartItems['cartItem'])) {
            $dataCartItems['cartItem']['qty'] = $qtyRequest;
            $this->_request->setParam('cartItem',$dataCartItems['cartItem']);
        }

        return $qtyRequest;
    }

}