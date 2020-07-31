<?php
namespace Riki\AdvancedInventory\Api\Data;

interface OutOfStockInterface
{
    const ENTITY_ID = 'entity_id';
    const ORIGINAL_ORDER_ID = 'original_order_id';
    const GENERATED_ORDER_ID = 'generated_order_id';
    const QUOTE_ID = 'quote_id';
    const QUOTE_ITEM_ID = 'quote_item_id';
    const PRODUCT_ID = 'product_id';
    const QTY = 'qty';
    const PRIZE_ID = 'prize_id';
    const SALESRULE_ID = 'salesrule_id';
    const SUBSCRIPTION_PROFILE_ID = 'subscription_profile_id';
    const STORE_ID = 'store_id';
    const CUSTOMER_ID = 'customer_id';
    const MACHINE_SKU_ID = 'machine_sku_id';

    /**
     * Get entity_id
     *
     * @return int
     */
    public function getEntityId();

    /**
     * Set entity_id
     *
     * @param string|int $entityId entityId
     *
     * @return \Riki\AdvancedInventory\Api\Data\OutOfStockInterface
     */
    public function setEntityId($entityId);

    /**
     * Get original_order_id
     *
     * @return int|null
     */
    public function getOriginalOrderId();

    /**
     * Set original_order_id
     *
     * @param string|int $originalOrderId original_order_id
     *
     * @return \Riki\AdvancedInventory\Api\Data\OutOfStockInterface
     */
    public function setOriginalOrderId($originalOrderId);

    /**
     *  Get generated_order_id
     *
     * @return int|null
     */
    public function getGeneratedOrderId();

    /**
     * Set generated_order_id
     *
     * @param string|int $generatedOrderId generatedOrderId
     *
     * @return \Riki\AdvancedInventory\Api\Data\OutOfStockInterface
     */
    public function setGeneratedOrderId($generatedOrderId);

    /**
     * Get quote_id
     *
     * @return int|null
     */
    public function getQuoteId();

    /**
     * Set quote_id
     *
     * @param string|int $quoteId quoteId
     *
     * @return \Riki\AdvancedInventory\Api\Data\OutOfStockInterface
     */
    public function setQuoteId($quoteId);

    /**
     * Get quote_item_id
     *
     * @return int|null
     */
    public function getQuoteItemId();

    /**
     * Set quote_item_id
     *
     * @param string|int $quoteItemId quoteItemId
     *
     * @return \Riki\AdvancedInventory\Api\Data\OutOfStockInterface
     */
    public function setQuoteItemId($quoteItemId);


    /**
     * Get product_id
     *
     * @return int|null
     */
    public function getProductId();

    /**
     * Set product_id
     *
     * @param string|int $productId productId
     *
     * @return \Riki\AdvancedInventory\Api\Data\OutOfStockInterface
     */
    public function setProductId($productId);

    /**
     * Get qty
     *
     * @return float|null
     */
    public function getQty();

    /**
     * Set qty
     *
     * @param string|int $qty qty
     *
     * @return \Riki\AdvancedInventory\Api\Data\OutOfStockInterface
     */
    public function setQty($qty);

    /**
     * Get prize_id
     *
     * @return string|int
     */
    public function getPrizeId();

    /**
     * Set prize_id
     *
     * @param string|int $prizeId prizeId
     *
     * @return \Riki\AdvancedInventory\Api\Data\OutOfStockInterface
     */
    public function setPrizeId($prizeId);

    /**
     * Get salesrule_id
     *
     * @return string|int
     */
    public function getSalesruleId();

    /**
     * Set salesrule_id
     *
     * @param string|int $salesruleId salesruleId
     *
     * @return \Riki\AdvancedInventory\Api\Data\OutOfStockInterface
     */
    public function setSalesruleId($salesruleId);

    /**
     * Get subscription_profile_id
     *
     * @return string|int
     */
    public function getSubscriptionProfileId();

    /**
     * Set subscription_profile_id
     *
     * @param string|int $subscriptionProfileId subscriptionProfileId
     *
     * @return \Riki\AdvancedInventory\Api\Data\OutOfStockInterface
     */
    public function setSubscriptionProfileId($subscriptionProfileId);

    /**
     * Get store_id
     *
     * @return string|int
     */
    public function getStoreId();

    /**
     * Set store_id
     *
     * @param string|int $storeId storeId
     *
     * @return \Riki\AdvancedInventory\Api\Data\OutOfStockInterface
     */
    public function setStoreId($storeId);

    /**
     * Get customer_id
     *
     * @return string|int
     */
    public function getCustomerId();

    /**
     * Set customer_id
     *
     * @param $customerId
     *
     * @return \Riki\AdvancedInventory\Api\Data\OutOfStockInterface
     */
    public function setCustomerId($customerId);

    /**
     * Get machine_sku_id
     *
     * @return string|int
     */
    public function getMachineSkuId();

    /**
     * Set machine_sku_id
     *
     * @param $machineSkuId
     *
     * @return \Riki\AdvancedInventory\Api\Data\OutOfStockInterface
     */
    public function setMachineSkuId($machineSkuId);
}
