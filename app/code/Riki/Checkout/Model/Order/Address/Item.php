<?php

namespace Riki\Checkout\Model\Order\Address;

use \Magento\Sales\Model\Order;

/**
 * @method \Magento\Quote\Model\ResourceModel\Quote\Address\Item _getResource()
 * @method \Magento\Quote\Model\ResourceModel\Quote\Address\Item getResource()
 * @method int getParentItemId()
 * @method \Magento\Quote\Model\Quote\Address\Item setParentItemId(int $value)
 * @method int getQuoteAddressId()
 * @method \Magento\Quote\Model\Quote\Address\Item setQuoteAddressId(int $value)
 * @method int getQuoteItemId()
 * @method \Magento\Quote\Model\Quote\Address\Item setQuoteItemId(int $value)
 * @method string getCreatedAt()
 * @method \Magento\Quote\Model\Quote\Address\Item setCreatedAt(string $value)
 * @method string getUpdatedAt()
 * @method \Magento\Quote\Model\Quote\Address\Item setUpdatedAt(string $value)
 * @method string getAppliedRuleIds()
 * @method \Magento\Quote\Model\Quote\Address\Item setAppliedRuleIds(string $value)
 * @method string getAdditionalData()
 * @method \Magento\Quote\Model\Quote\Address\Item setAdditionalData(string $value)
 * @method float getWeight()
 * @method \Magento\Quote\Model\Quote\Address\Item setWeight(float $value)
 * @method \Magento\Quote\Model\Quote\Address\Item setQty(float $value)
 * @method float getDiscountAmount()
 * @method \Magento\Quote\Model\Quote\Address\Item setDiscountAmount(float $value)
 * @method \Magento\Quote\Model\Quote\Address\Item setTaxAmount(float $value)
 * @method float getRowTotal()
 * @method \Magento\Quote\Model\Quote\Address\Item setRowTotal(float $value)
 * @method float getBaseRowTotal()
 * @method \Magento\Quote\Model\Quote\Address\Item setBaseRowTotal(float $value)
 * @method float getRowTotalWithDiscount()
 * @method \Magento\Quote\Model\Quote\Address\Item setRowTotalWithDiscount(float $value)
 * @method float getBaseDiscountAmount()
 * @method \Magento\Quote\Model\Quote\Address\Item setBaseDiscountAmount(float $value)
 * @method \Magento\Quote\Model\Quote\Address\Item setBaseTaxAmount(float $value)
 * @method float getRowWeight()
 * @method \Magento\Quote\Model\Quote\Address\Item setRowWeight(float $value)
 * @method int getProductId()
 * @method \Magento\Quote\Model\Quote\Address\Item setProductId(int $value)
 * @method int getSuperProductId()
 * @method \Magento\Quote\Model\Quote\Address\Item setSuperProductId(int $value)
 * @method int getParentProductId()
 * @method \Magento\Quote\Model\Quote\Address\Item setParentProductId(int $value)
 * @method string getSku()
 * @method \Magento\Quote\Model\Quote\Address\Item setSku(string $value)
 * @method string getImage()
 * @method \Magento\Quote\Model\Quote\Address\Item setImage(string $value)
 * @method string getName()
 * @method \Magento\Quote\Model\Quote\Address\Item setName(string $value)
 * @method string getDescription()
 * @method \Magento\Quote\Model\Quote\Address\Item setDescription(string $value)
 * @method int getFreeShipping()
 * @method \Magento\Quote\Model\Quote\Address\Item setFreeShipping(int $value)
 * @method int getIsQtyDecimal()
 * @method \Magento\Quote\Model\Quote\Address\Item setIsQtyDecimal(int $value)
 * @method float getDiscountPercent()
 * @method \Magento\Quote\Model\Quote\Address\Item setDiscountPercent(float $value)
 * @method int getNoDiscount()
 * @method \Magento\Quote\Model\Quote\Address\Item setNoDiscount(int $value)
 * @method float getTaxPercent()
 * @method \Magento\Quote\Model\Quote\Address\Item setTaxPercent(float $value)
 * @method float getBasePrice()
 * @method \Magento\Quote\Model\Quote\Address\Item setBasePrice(float $value)
 * @method float getBaseCost()
 * @method \Magento\Quote\Model\Quote\Address\Item setBaseCost(float $value)
 * @method float getPriceInclTax()
 * @method \Magento\Quote\Model\Quote\Address\Item setPriceInclTax(float $value)
 * @method float getBasePriceInclTax()
 * @method \Magento\Quote\Model\Quote\Address\Item setBasePriceInclTax(float $value)
 * @method float getRowTotalInclTax()
 * @method \Magento\Quote\Model\Quote\Address\Item setRowTotalInclTax(float $value)
 * @method float getBaseRowTotalInclTax()
 * @method \Magento\Quote\Model\Quote\Address\Item setBaseRowTotalInclTax(float $value)
 * @method int getGiftMessageId()
 * @method \Magento\Quote\Model\Quote\Address\Item setGiftMessageId(int $value)
 * @method float getDiscountTaxCompensationAmount()
 * @method \Magento\Quote\Model\Quote\Address\Item setDiscountTaxCompensationAmount(float $value)
 * @method float getBaseDiscountTaxCompensationAmount()
 * @method \Magento\Quote\Model\Quote\Address\Item setBaseDiscountTaxCompensationAmount(float $value)
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Item extends \Magento\Sales\Model\AbstractModel
{
    /**
     * Quote address model object
     *
     * @var \Magento\Sales\Model\Order\Address
     */
    protected $_address;

    /**
     * @var Order
     */
    protected $_order;

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Checkout\Model\ResourceModel\Order\Address\Item');
    }

    /**
     * @return $this|\Magento\Quote\Model\Quote\Item\AbstractItem
     */
    public function beforeSave()
    {
        parent::beforeSave();
        if ($this->getAddress()) {
            $this->setOrderAddressId($this->getAddress()->getId());
        }
        return $this;
    }

    /**
     * Declare address model
     *
     * @param \Magento\Quote\Model\Quote\Address $address
     * @return $this
     */
    public function setAddress(\Magento\Sales\Model\Order\Address $address)
    {
        $this->_address = $address;
        $this->_order = $address->getOrder();
        return $this;
    }

    /**
     * Retrieve address model
     *
     * @return \Magento\Quote\Model\Quote\Address
     */
    public function getAddress()
    {
        return $this->_address;
    }

    /**
     * Retrieve quote model instance
     *
     * @return Quote
     */
    public function getOrder()
    {
        return $this->_order;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @return $this
     */
    public function importOrderItem(\Magento\Sales\Model\Order\Item $orderItem)
    {
        $this->_order = $orderItem->getOrder();
        $this->setOrderItem(
            $orderItem
        )->setOrderItemId(
            $orderItem->getId()
        )->setProductId(
            $orderItem->getProductId()
        )->setProduct(
            $orderItem->getProduct()
        )->setSku(
            $orderItem->getSku()
        )->setName(
            $orderItem->getName()
        )->setDescription(
            $orderItem->getDescription()
        )->setWeight(
            $orderItem->getWeight()
        )->setPrice(
            $orderItem->getPrice()
        )->setCost(
            $orderItem->getCost()
        );

        if (!$this->hasQty()) {
            $this->setQty($orderItem->getQty());
        }
        $this->setOrderItemImported(true);
        return $this;
    }
}

