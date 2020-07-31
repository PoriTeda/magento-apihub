<?php
/**
 * @category    Magento
 * @package     Magento_TargetRule
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Customer\Plugin\Model;

use Magento\ImportExport\Model\Import as ImportModel;
use Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor as ProductRuleProcessor;
use Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Processor as RuleProductProcessor;

class Import extends \Magento\TargetRule\Model\Indexer\TargetRule\Plugin\Import
{
    protected $resource;

    public function __construct(
        ProductRuleProcessor $productRuleProcessor,
        RuleProductProcessor $ruleProductProcessor,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->resource = $resourceConnection;
        parent::__construct($productRuleProcessor, $ruleProductProcessor);
    }

    /**
     * Invalidate target rule indexer
     *
     * @param ImportModel $subject
     * @param bool $result
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterImportSource(ImportModel $subject, $result)
    {
        $sql = "CREATE TABLE cataloginventory_stock_item_temp LIKE cataloginventory_stock_item;";
        $sql2 ="INSERT INTO `cataloginventory_stock_item_temp` (`item_id`, `product_id`, `stock_id`, `qty`, `min_qty`, `use_config_min_qty`, `is_qty_decimal`, `backorders`, `use_config_backorders`, `min_sale_qty`, `use_config_min_sale_qty`, `max_sale_qty`, `use_config_max_sale_qty`, `is_in_stock`, `low_stock_date`, `notify_stock_qty`, `use_config_notify_stock_qty`, `manage_stock`, `use_config_manage_stock`, `stock_status_changed_auto`, `use_config_qty_increments`, `qty_increments`, `use_config_enable_qty_inc`, `enable_qty_increments`, `is_decimal_divided`, `website_id`, `deferred_stock_update`, `use_config_deferred_stock_update`) SELECT * FROM cataloginventory_stock_item WHERE website_id <> 0;";
        $sql3 ="DELETE FROM cataloginventory_stock_item WHERE website_id <> 0;";
        $sql4 ="INSERT IGNORE INTO `cataloginventory_stock_item` (`item_id`, `product_id`, `stock_id`, `qty`, `min_qty`, `use_config_min_qty`, `is_qty_decimal`, `backorders`, `use_config_backorders`, `min_sale_qty`, `use_config_min_sale_qty`, `max_sale_qty`, `use_config_max_sale_qty`, `is_in_stock`, `low_stock_date`, `notify_stock_qty`, `use_config_notify_stock_qty`, `manage_stock`, `use_config_manage_stock`, `stock_status_changed_auto`, `use_config_qty_increments`, `qty_increments`, `use_config_enable_qty_inc`, `enable_qty_increments`, `is_decimal_divided`, `website_id`, `deferred_stock_update`, `use_config_deferred_stock_update`) SELECT `item_id`, `product_id`, `stock_id`, `qty`, `min_qty`, `use_config_min_qty`, `is_qty_decimal`, `backorders`, `use_config_backorders`, `min_sale_qty`, `use_config_min_sale_qty`, `max_sale_qty`, `use_config_max_sale_qty`, `is_in_stock`, `low_stock_date`, `notify_stock_qty`, `use_config_notify_stock_qty`, `manage_stock`, `use_config_manage_stock`, `stock_status_changed_auto`, `use_config_qty_increments`, `qty_increments`, `use_config_enable_qty_inc`, `enable_qty_increments`, `is_decimal_divided`, 0, `deferred_stock_update`, `use_config_deferred_stock_update` FROM cataloginventory_stock_item_temp;";
        $sql5 ="DROP TABLE cataloginventory_stock_item_temp;";
        $connection = $this->resource->getConnection();

        // Drop table cataloginventory_stock_item_temp before doing the next step.
        if ($connection->isTableExists('cataloginventory_stock_item_temp')) {
            $connection->query($sql5);
        }

        // [RIKI-3117] A workaround to change all website_id to 0 in table cataloginventory_stock_item
        $connection->query($sql);
        $connection->query($sql2);
        $connection->query($sql3);
        $connection->query($sql4);
        $connection->query($sql5);
        
        return $result;
    }
}
