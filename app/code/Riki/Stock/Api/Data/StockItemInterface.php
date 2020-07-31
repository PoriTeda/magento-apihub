<?php
/**
 * Stock
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Stock
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\Stock\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\CatalogInventory\Api\Data\StockItemExtensionInterface;

/**
 * Stock
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Stock
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
interface StockItemInterface extends ExtensibleDataInterface
{
    const BACKORDERS_NO = 0;

    const ITEM_ID = 'item_id';
    const PRODUCT_ID = 'product_id';
    const STOCK_ID = 'stock_id';
    const QTY = 'qty';
    const IS_QTY_DECIMAL = 'is_qty_decimal';
    const SHOW_DEFAULT_NOTIFICATION_MESSAGE = 'show_default_notification_message';

    const USE_CONFIG_MIN_QTY = 'use_config_min_qty';
    const MIN_QTY = 'min_qty';

    const USE_CONFIG_MIN_SALE_QTY = 'use_config_min_sale_qty';
    const MIN_SALE_QTY = 'min_sale_qty';

    const USE_CONFIG_MAX_SALE_QTY = 'use_config_max_sale_qty';
    const MAX_SALE_QTY = 'max_sale_qty';

    const USE_CONFIG_BACKORDERS = 'use_config_backorders';
    const BACKORDERS = 'backorders';

    const USE_CONFIG_NOTIFY_STOCK_QTY = 'use_config_notify_stock_qty';
    const NOTIFY_STOCK_QTY = 'notify_stock_qty';

    const USE_CONFIG_QTY_INCREMENTS = 'use_config_qty_increments';
    const QTY_INCREMENTS = 'qty_increments';

    const USE_CONFIG_ENABLE_QTY_INC = 'use_config_enable_qty_inc';
    const ENABLE_QTY_INCREMENTS = 'enable_qty_increments';

    const USE_CONFIG_MANAGE_STOCK = 'use_config_manage_stock';
    const MANAGE_STOCK = 'manage_stock';

    const IS_IN_STOCK = 'is_in_stock';
    const LOW_STOCK_DATE = 'low_stock_date';
    const IS_DECIMAL_DIVIDED = 'is_decimal_divided';
    const STOCK_STATUS_CHANGED_AUTO = 'stock_status_changed_auto';

    const STORE_ID = 'store_id';
    const CUSTOMER_GROUP_ID = 'customer_group_id';

    /**
     * Get Item
     *
     * @return int|null
     */
    public function getItemId();

    /**
     * Set Item
     *
     * @param int $itemId itemId
     *
     * @return $this
     */
    public function setItemId($itemId);

    /**
     * Get product id
     *
     * @return int|null
     */
    public function getProductId();

    /**
     * Set product id
     *
     * @param int $productId productId
     *
     * @return $this
     */
    public function setProductId($productId);

    /**
     * Retrieve stock identifier
     *
     * @return int|null
     */
    public function getStockId();

    /**
     * Set stock identifier
     *
     * @param int $stockId stockId
     *
     * @return $this
     */
    public function setStockId($stockId);

    /**
     * Get Qty
     *
     * @return float
     */
    public function getQty();

    /**
     * Set Qty
     *
     * @param float $qty qty
     *
     * @return $this
     */
    public function setQty($qty);

    /**
     * Retrieve Stock Availability
     *
     * @return bool|int
     */
    public function getIsInStock();

    /**
     * Set Stock Availability
     *
     * @param bool|int $isInStock isInStock
     *
     * @return $this
     */
    public function setIsInStock($isInStock);

    /**
     * Get is qty decimal
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsQtyDecimal();

    /**
     * Set is qty decimal
     *
     * @param bool $isQtyDecimal isQtyDecimal
     *
     * @return $this
     */
    public function setIsQtyDecimal($isQtyDecimal);

    /**
     * Get show default notification message
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getShowDefaultNotificationMessage();

    /**
     * Get Use config min qty
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getUseConfigMinQty();

    /**
     * Set Use config min qty
     *
     * @param bool $useConfigMinQty useConfigMinQty
     *
     * @return $this
     */
    public function setUseConfigMinQty($useConfigMinQty);

    /**
     * Retrieve minimal quantity available for item status in stock
     *
     * @return float
     */
    public function getMinQty();

    /**
     * Set minimal quantity available for item status in stock
     *
     * @param float $minQty minQty
     *
     * @return $this
     */
    public function setMinQty($minQty);

    /**
     * Get Use Config Min Sale Qty
     *
     * @return int
     */
    public function getUseConfigMinSaleQty();

    /**
     * Set Use Config Min Sale Qty
     *
     * @param int $useConfigMinSaleQty useConfigMinSaleQty
     *
     * @return $this
     */
    public function setUseConfigMinSaleQty($useConfigMinSaleQty);

    /**
     * Get min sale Qty
     *
     * @return float
     */
    public function getMinSaleQty();

    /**
     * Set Minimum Qty Allowed in Shopping Cart or NULL when there is no limitation
     *
     * @param float $minSaleQty minSaleQty
     *
     * @return $this
     */
    public function setMinSaleQty($minSaleQty);

    /**
     * Get Use Config Max Sale Qty
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getUseConfigMaxSaleQty();

    /**
     * Set Use Config Max Sale Qty
     *
     * @param bool $useConfigMaxSaleQty useConfigMaxSaleQty
     *
     * @return $this
     */
    public function setUseConfigMaxSaleQty($useConfigMaxSaleQty);

    /**
     * Retrieve Maximum Qty Allowed in Shopping Cart data wrapper
     *
     * @return float
     */
    public function getMaxSaleQty();

    /**
     * Set Maximum Qty Allowed in Shopping Cart data wrapper
     *
     * @param float $maxSaleQty maxSaleQty
     *
     * @return $this
     */
    public function setMaxSaleQty($maxSaleQty);

    /**
     * Get Use Config Back orders
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getUseConfigBackorders();

    /**
     * Set Use Config Back orders
     *
     * @param bool $useConfigBackorders useConfigBackorders
     *
     * @return $this
     */
    public function setUseConfigBackorders($useConfigBackorders);

    /**
     * Retrieve backorders status
     *
     * @return int
     */
    public function getBackorders();

    /**
     * Set backOrders status
     *
     * @param int $backOrders backOrders
     *
     * @return $this
     */
    public function setBackorders($backOrders);

    /**
     * Get Use Config Notify Stock Qty
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getUseConfigNotifyStockQty();

    /**
     * Set Use Config Notify Stock Qty
     *
     * @param bool $useConfigNotifyStockQty useConfigNotifyStockQty
     *
     * @return $this
     */
    public function setUseConfigNotifyStockQty($useConfigNotifyStockQty);

    /**
     * Retrieve Notify for Quantity Below data wrapper
     *
     * @return float
     */
    public function getNotifyStockQty();

    /**
     * Set Notify for Quantity Below data wrapper
     *
     * @param float $notifyStockQty notifyStockQty
     *
     * @return $this
     */
    public function setNotifyStockQty($notifyStockQty);

    /**
     * Set Use Config Qty Increments
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getUseConfigQtyIncrements();

    /**
     * Set Use Config Qty Increments
     *
     * @param bool $useConfigQtyIncrements useConfigQtyIncrements
     *
     * @return $this
     */
    public function setUseConfigQtyIncrements($useConfigQtyIncrements);

    /**
     * Retrieve Quantity Increments data wrapper
     *
     * @return float|false
     */
    public function getQtyIncrements();

    /**
     * Set Quantity Increments data wrapper
     *
     * @param float $qtyIncrements qtyIncrements
     *
     * @return $this
     */
    public function setQtyIncrements($qtyIncrements);

    /**
     * Get Use Config Enable Qty Inc
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getUseConfigEnableQtyInc();

    /**
     * Set Use Config Enable Qty Inc
     *
     * @param bool $useConfigEnableQtyInc useConfigEnableQtyInc
     *
     * @return $this
     */
    public function setUseConfigEnableQtyInc($useConfigEnableQtyInc);

    /**
     * Retrieve whether Quantity Increments is enabled
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getEnableQtyIncrements();

    /**
     * Set whether Quantity Increments is enabled
     *
     * @param bool $enableQtyIncrements enableQtyIncrements
     * 
     * @return $this
     */
    public function setEnableQtyIncrements($enableQtyIncrements);

    /**
     * Set Enable Qty Increments
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getUseConfigManageStock();

    /**
     * Set Use Config Manage Stock
     *
     * @param bool $useConfigManageStock useConfigManageStock
     *
     * @return $this
     */
    public function setUseConfigManageStock($useConfigManageStock);

    /**
     * Retrieve can Manage Stock
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getManageStock();

    /**
     * Set Manage Stock
     *
     * @param bool $manageStock manageStock
     *
     * @return $this
     */
    public function setManageStock($manageStock);

    /**
     * Get Low Stock Date
     *
     * @return string
     */
    public function getLowStockDate();

    /**
     * Set Low Stock Date
     *
     * @param string $lowStockDate lowStockDate
     *
     * @return $this
     */
    public function setLowStockDate($lowStockDate);

    /**
     * Get Is Decimal Divided
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsDecimalDivided();

    /**
     * Get Is Decimal Divided
     *
     * @param bool $isDecimalDivided isDecimalDivided
     *
     * @return $this
     */
    public function setIsDecimalDivided($isDecimalDivided);

    /**
     * Get Stock Status Changed Auto
     *
     * @return int
     */
    public function getStockStatusChangedAuto();

    /**
     * Set Stock Status Changed Auto
     *
     * @param int $stockStatusChangedAuto stockStatusChangedAuto
     *
     * @return $this
     */
    public function setStockStatusChangedAuto($stockStatusChangedAuto);

    /**
     * Retrieve existing extension attributes object or create a new one.
     *
     * @return \Magento\CatalogInventory\Api\Data\StockItemExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     *
     * @param StockItemExtensionInterface $extensionAttributes extensionAttributes
     *
     * @return $this
     */
    public function setExtensionAttributes(
        \Magento\CatalogInventory\Api\Data\StockItemExtensionInterface $extensionAttributes
    );

}
