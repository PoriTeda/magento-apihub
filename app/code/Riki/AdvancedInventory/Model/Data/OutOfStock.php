<?php
namespace Riki\AdvancedInventory\Model\Data;

class OutOfStock extends \Magento\Framework\DataObject implements \Riki\AdvancedInventory\Api\Data\OutOfStockInterface
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getEntityId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    /**
     * {@inheritdoc}
     *
     * @param int|string $entityId
     *
     * @return $this
     */
    public function setEntityId($entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed
     */
    public function getQuoteId()
    {
        return $this->getData(self::QUOTE_ID);
    }

    /**
     * {@inheritdoc}
     *
     * @param int|string $quoteId
     *
     * @return $this
     */
    public function setQuoteId($quoteId)
    {
        return $this->setData(self::QUOTE_ID, $quoteId);
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed
     */
    public function getOriginalOrderId()
    {
        return $this->getData(self::ORIGINAL_ORDER_ID);
    }

    /**
     * {@inheritdoc}
     *
     * @param int|string $originalOrderId
     *
     * @return $this
     */
    public function setOriginalOrderId($originalOrderId)
    {
        return $this->setData(self::ORIGINAL_ORDER_ID, $originalOrderId);
    }

    /**
     * @inheritdoc
     *
     * @return string|int
     */
    public function getGeneratedOrderId()
    {
        return $this->getData(self::GENERATED_ORDER_ID);
    }

    /**
     * @inheritdoc
     */
    public function setGeneratedOrderId($generatedOrderId)
    {
        return $this->setData(self::GENERATED_ORDER_ID, $generatedOrderId);
    }

    /**
     * @inheritdoc
     *
     * @return string|int
     */
    public function getProductId()
    {
        return $this->getData(self::PRODUCT_ID);
    }

    /**
     * {@inheritdoc}
     *
     * @param int|string $productId
     *
     * @return $this
     */
    public function setProductId($productId)
    {
        return $this->setData(self::PRODUCT_ID, $productId);
    }

    /**
     * {@inheritdoc}
     *
     * @return string|int
     */
    public function getQty()
    {
        return $this->getData(self::QTY);
    }

    /**
     * {@inheritdoc}
     *
     * @param int|string $qty
     *
     * @return $this
     */
    public function setQty($qty)
    {
        return $this->setData(self::QTY, $qty);
    }

    /**
     * {@inheritdoc}
     *
     * @return string|int
     */
    public function getPrizeId()
    {
        return $this->getData(self::PRIZE_ID);
    }

    /**
     * {@inheritdoc}
     *
     * @param int|string $prizeId
     *
     * @return $this
     */
    public function setPrizeId($prizeId)
    {
        return $this->setData(self::PRIZE_ID, $prizeId);
    }

    /**
     * {@inheritdoc}
     *
     * @return string|int
     */
    public function getQuoteItemId()
    {
        return $this->getData(self::QUOTE_ITEM_ID);
    }

    /**
     * {@inheritdoc}
     *
     * @param int|string $quoteItemId
     *
     * @return $this
     */
    public function setQuoteItemId($quoteItemId)
    {
        return $this->setData(self::QUOTE_ITEM_ID, $quoteItemId);
    }

    /**
     * {@inheritdoc}
     *
     * @return string|int
     */
    public function getSalesruleId()
    {
        return $this->getData(self::SALESRULE_ID);
    }

    /**
     * {@inheritdoc}
     *
     * @param int|string $salesruleId
     *
     * @return $this
     */
    public function setSalesruleId($salesruleId)
    {
        return $this->setData(self::SALESRULE_ID, $salesruleId);
    }

    /**
     * {@inheritdoc}
     *
     * @return string|int
     */
    public function getSubscriptionProfileId()
    {
        return $this->getData(self::SUBSCRIPTION_PROFILE_ID);
    }

    /**
     * {@inheritdoc}
     *
     * @param int|string $subscriptionProfileId
     *
     * @return $this
     */
    public function setSubscriptionProfileId($subscriptionProfileId)
    {
        return $this->setData(self::SUBSCRIPTION_PROFILE_ID, $subscriptionProfileId);
    }

    /**
     * {@inheritdoc}
     *
     * @return string|int
     */
    public function getStoreId()
    {
        return $this->getData(self::STORE_ID);
    }

    /**
     * {@inheritdoc}
     *
     * @param int|string $storeId
     *
     * @return $this
     */
    public function setStoreId($storeId)
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    /**
     * {@inheritdoc}
     *
     * @return string|int
     */
    public function getCustomerId()
    {
        return $this->getData(self::CUSTOMER_ID);
    }

    /**
     * {@inheritdoc}
     *
     * @param $customerId
     *
     * @return $this
     */
    public function setCustomerId($customerId)
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * {@inheritdoc}
     *
     * @return string|int
     */
    public function getMachineSkuId()
    {
        return $this->getData(self::MACHINE_SKU_ID);
    }

    /**
     * {@inheritdoc}
     *
     * @param $machineSkuId
     *
     * @return $this
     */
    public function setMachineSkuId($machineSkuId)
    {
        return $this->setData(self::MACHINE_SKU_ID, $machineSkuId);
    }
}