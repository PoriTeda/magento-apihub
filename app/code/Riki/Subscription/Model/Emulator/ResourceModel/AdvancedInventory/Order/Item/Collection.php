<?php

namespace Riki\Subscription\Model\Emulator\ResourceModel\AdvancedInventory\Order\Item;

use Riki\Subscription\Model\Emulator\Config ;

class Collection extends \Wyomind\AdvancedInventory\Model\ResourceModel\Order\Item\Collection
{
    /**
     * Model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Subscription\Model\Emulator\Order\Item', '\Riki\Subscription\Model\Emulator\ResourceModel\Order\Item');
    }

    public function getAssignationByOrderId(
        $orderId,
        $itemId = false
    ) {

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
                    "name" => "name",
                    "sku" => "sku",
                    "order_id" => "order_id",
                    "item_id" => "item_id",
                    "parent_item_id" => "parent_item_id",
                    "product_id" => "product_id",
                    "price_incl_tax" => "price_incl_tax",
                    "base_price_incl_tax" => "base_price_incl_tax",
                    "discount_amount" => "discount_amount",
                    "base_discount_amount" => "base_discount_amount",
                    "gw_price" => "gw_price",
                    "gw_base_price" => "gw_base_price",
                    "gw_tax_amount" => "gw_tax_amount",
                    "gw_base_tax_amount" => "gw_base_tax_amount",
                    "tax_riki" => "tax_riki",
                    "product_type" => "product_type",
                    "qty_ordered" => "qty_ordered",
                    "delivery_type" =>  "delivery_type",
                    "qty_canceled" => new \Zend_Db_Expr("`main_table`.`qty_canceled`"),
                    "qty_refunded" => new \Zend_Db_Expr("`main_table`.`qty_refunded`"),
                ]
            );


        $itemIds = [];
        $productIds = [];
        $parentItemIds = [];

        foreach ($this as $item) {
            $itemIds[] = $item->getId();
            $productIds[] = $item->getProductId();
            $parentItemIds[] = $item->getParentItemId();
        }

        $parentItemsRefundQtyData = $this->_helperData->getSimulateOrderItemQtyRefundedCancelled($parentItemIds);

        $advancedInventoryAssignationData = $this->_helperData->getAdvancedInventoryQtysData($itemIds);

        $advancedInventoryItemData = $this->_helperData->getAdvancedInventoryStockStatus($productIds);

        foreach ($this as $item) {
            if (isset($advancedInventoryItemData[$item->getProductId()])) {
                $item->setMultistockEnabled($advancedInventoryItemData[$item->getProductId()]);
            } else {
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

            foreach ($advancedInventoryAssignationData as $aiItemData) {
                if ($aiItemData['item_id'] == $item->getId()) {
                    foreach ($parentItemsRefundQtyData as $parentItemRefundQtyData) {
                        if ($parentItemRefundQtyData['item_id'] == $item->getParentItemId()) {
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

            foreach ($exData as $key => $val) {
                $item->setData($key, $val);
            }
        }
        return $this;
    }
}
