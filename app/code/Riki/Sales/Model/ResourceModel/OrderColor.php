<?php

namespace Riki\Sales\Model\ResourceModel;

use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use \Psr\Log\Logger\LoggerInterface;

class OrderColor extends AbstractDb
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_connectionSales;
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Psr\Log\LoggerInterface $logger,
        $connectionName = null
    ) {
        $this->_logger = $logger;
        $this->_connectionSales = $context->getResources()->getConnection('sales');
        parent::__construct($context, $connectionName);
    }
    /**
     * Initialize resource
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('riki_order_status_color', 'status_code');
    }

    public function savePromotionWBSForOrderItem($wbsData, $item)
    {
        try {
            $connection = $this->_connectionSales;
            $data = $this->_prepareDataForTable(
                new \Magento\Framework\DataObject($wbsData),
                $this->getTable('sales_order_item')
            );

            $connection->update(
                $this->getTable('sales_order_item'),
                $data,
                [
                    'item_id = ?' => $item->getItemId()
                ]
            );
            // save free_payment_wbs, free_delivery_wbs on Order level
            if (array_key_exists('free_payment_wbs', $wbsData) || array_key_exists('free_delivery_wbs', $wbsData)) {
                unset($wbsData['foc_wbs']);
                unset($wbsData['account_code']);
                unset($wbsData['sap_condition_type']);
                $this->savePromotionWBSForOrder($wbsData, $item->getOrder());
            }
        } catch (\Exception $e) {
            // just log to keep continue checkout processing
            $this->_logger->debug($e->getMessage());
        }
    }

    /**
     * Save catalog rule to items for export
     *
     * @param $catalogRuleData
     * @param $item
     */

    public function saveCatalogRuleForOrderItem($catalogRuleData, $item)
    {
        try {
            $connection = $this->_connectionSales;
            $data = $this->_prepareDataForTable(
                new \Magento\Framework\DataObject($catalogRuleData),
                $this->getTable('sales_order_item')
            );

            $connection->update(
                $this->getTable('sales_order_item'),
                $data,
                [
                    'item_id = ?' => $item->getItemId()
                ]
            );
        } catch (\Exception $e) {
            // just log to keep continue checkout processing
            $this->_logger->critical($e);
        }
    }

    public function savePromotionWBSForOrder($wbsData, $order)
    {
        try {
            $connection = $this->getConnection();
            $data = $this->_prepareDataForTable(
                new \Magento\Framework\DataObject($wbsData),
                $this->getTable('sales_order')
            );

            $connection->update(
                $this->getTable('sales_order'),
                $data,
                [
                    'entity_id = ?' => $order->getId()
                ]
            );
        } catch (\Exception $e) {
            // just log to keep continue checkout processing
            $this->_logger->debug($e->getMessage());
        }
    }
}

