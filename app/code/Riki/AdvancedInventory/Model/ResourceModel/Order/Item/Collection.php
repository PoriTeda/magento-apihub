<?php

namespace Riki\AdvancedInventory\Model\ResourceModel\Order\Item;

class Collection extends \Wyomind\AdvancedInventory\Model\ResourceModel\Order\Item\Collection
{

    /**
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot $entitySnapshot
     * @param \Wyomind\AdvancedInventory\Helper\Data $helperData
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|null $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb|null $resource
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot $entitySnapshot,
        \Wyomind\AdvancedInventory\Helper\Data $helperData,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ){
      parent::__construct(
          $entityFactory,
          $logger,
          $fetchStrategy,
          $eventManager,
          $entitySnapshot,
          $helperData,
          $connection,
          $resource
      );
    }


    public function getAssignationByOrderId($orderId, $itemId = false)
    {
        $this->addFieldToSelect('item_id');
        if ($itemId) {
            $this->addFieldToFilter("main_table.item_id", ["eq" => $itemId]);
        }
        $this->addFieldToFilter("order_id", ["eq" => $orderId]);
        $or = [];

        foreach ($this->_helperData->getProductTypes() as $type) {
            $or[] = ["eq" => $type];
        }

        $this->addFieldToFilter("product_type", [$or, ['eq' => "grouped"]]);

        $this->getSelect()
            ->columns(
                [
                    'name' => 'name',
                    'sku' => 'sku',
                    'order_id' => 'order_id',
                    'item_id' => 'item_id',
                    'parent_item_id' => 'parent_item_id',
                    'product_id' => 'product_id',
                    'price_incl_tax' => 'price_incl_tax',
                    'base_price_incl_tax' => 'base_price_incl_tax',
                    'discount_amount' => 'discount_amount',
                    'base_discount_amount' => 'base_discount_amount',
                    'gw_price' => 'gw_price',
                    'gw_base_price' => 'gw_base_price',
                    'gw_tax_amount' => 'gw_tax_amount',
                    'gw_base_tax_amount' => 'gw_base_tax_amount',
                    'tax_riki' => 'tax_riki',
                    'tax_amount' => 'tax_amount',
                    'product_type' => 'product_type',
                    'delivery_type' => 'delivery_type',
                    'chirashi' => 'chirashi',
                    'qty_ordered' => 'qty_ordered',
                    'qty_canceled' => 'qty_canceled',
                    'qty_refunded' => 'qty_refunded'
                ]
            );

        $itemIds = [];
        $productIds = [];
        $parentItemIds = [];

        /** @var \Magento\Sales\Model\Order\Item $item */
        foreach($this as $item){
            $itemIds[] = $item->getId();
            $productIds[] = $item->getProductId();
            $parentItemIds[] = $item->getParentItemId();
        }

        $parentItemsRefundQtyData = $this->_helperData->getOrderItemQtyRefundedCancelled($parentItemIds);

        $advancedInventoryAssignationData = $this->_helperData->getAdvancedInventoryQtysData($itemIds);

        $advancedInventoryItemData = $this->_helperData->getAdvancedInventoryStockStatus($productIds);

        foreach($this as $item){
            if(isset($advancedInventoryItemData[$item->getProductId()])){
                $item->setMultistockEnabled($advancedInventoryItemData[$item->getProductId()]);
            }else{
                $item->setMultistockEnabled(0);
            }

            $exData = [
                'qty_unassigned'    =>  $item->getQtyOrdered(),
                'qty_to_assign'    =>  $item->getQtyOrdered(),
                'qty_assigned'    =>  0,
                'qty_returned'    =>  0
            ];

            $qtyCancelled = $item->getQtyCanceled();
            $qtyRefunded = $item->getQtyRefunded();

            foreach($advancedInventoryAssignationData as $aiItemData){
                if($aiItemData['item_id'] == $item->getId()){

                    foreach($parentItemsRefundQtyData as $parentItemRefundQtyData){
                        if($parentItemRefundQtyData['item_id'] == $item->getParentItemId()){
                            $qtyCancelled = $parentItemRefundQtyData['qty_canceled'];
                            $qtyRefunded = $parentItemRefundQtyData['qty_refunded'];
                            break;
                        }
                    }

                    $exData['qty_unassigned'] -= $aiItemData['qty_unassigned'];

                    $exData['qty_assigned'] = $aiItemData['qty_assigned'];
                    $exData['qty_returned'] = $aiItemData['qty_returned'];

                    break;
                }
            }

            $exData['qty_unassigned'] -= ($qtyRefunded + $qtyCancelled);
            $exData['qty_to_assign'] -= ($qtyRefunded + $qtyCancelled);

            foreach($exData as $key =>  $val){
                $item->setData($key, $val);
            }
        }
        return $this;
    }
}
