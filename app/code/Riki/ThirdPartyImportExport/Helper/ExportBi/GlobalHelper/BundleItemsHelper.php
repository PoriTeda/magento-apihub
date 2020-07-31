<?php

namespace Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper;

class BundleItemsHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_salesConnection;

    /*list bundle item*/
    protected $_bundleItems = [];

    /*bundle children item data that is re-calculated*/
    protected $_bundleChildrenItems = [];

    /** @var array $appliedStockPointDiscountItemsPrice */
    protected $appliedStockPointDiscountItemsPrice = [];

    /*list of bundle children item columns that will be applied new data */
    protected $_columns = [
        'price',
        'tax_riki',
        'discount_amount',
        'row_total',
        'gw_price',
        'discount_amount_excl_tax',
        'commission_amount',
        'price_incl_tax'
    ];

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $serializer;

    /**
     * BundleItemsHelper constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Riki\Sales\Helper\ConnectionHelper $connectionHelper
     * @param \Magento\Framework\Serialize\Serializer\Json|null $serializer
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Riki\Sales\Helper\ConnectionHelper $connectionHelper,
        \Magento\Framework\Serialize\Serializer\Json $serializer = null
    ) {
        parent::__construct($context);
        $this->serializer = $serializer ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Serialize\Serializer\Json::class);
        $this->_salesConnection = $connectionHelper->getSalesConnection();
    }

    /**
     * Re calculate data for order item (only apply for bundle children item)
     *
     * @param $item
     * @param null $parentItem used for simulate subscription order/shipment
     * @param array $allChildrenItems used for simulate subscription order/shipment
     * @return mixed
     */
    public function reCalculateOrderItem($item, $parentItem = null, $allChildrenItems = [])
    {
        /*only apply for bundle children item*/
        if (!empty($item['parent_item_id'])) {
            /*bundle data of this order item is not exist */
            if (empty($this->_bundleItems[$item['parent_item_id']])) {
                /*get parent item (bundle) of this item*/
                if (!$parentItem) {
                    $parentItem = $this->getOrderItemById($item['parent_item_id']);
                }

                if ($parentItem) {
                    $this->setBundleItems($parentItem, $allChildrenItems);
                }
            }
            /*apply new data for this item*/
            $item = $this->changeDataForChildrenItem($item);
        }

        return $item;
    }

    /**
     * Apply new data that was recalculated for order item
     *
     * @param $item
     * @return mixed
     */
    public function changeDataForChildrenItem($item)
    {
        /*make sure this is bundle children item*/
        if (!empty($item['parent_item_id'])) {
            /*check new data for this item is exist*/
            if (!empty($this->_bundleChildrenItems[ $item['item_id'] ])) {
                /*new data for this order item*/
                $newItemData = $this->_bundleChildrenItems[ $item['item_id'] ];

                foreach ($this->_columns as $col) {
                    if (isset($newItemData[$col])) {
                        /*replace old data by new data*/
                        $item[$col] = $newItemData[$col];
                    }
                }
            }
        }

        return $item;
    }

    /**
     * Add bundle item to array (make sure we do not re calculate it again)
     *
     * @param $item: parent_item
     */
    public function setBundleItems($item, $allChildrenItems = [])
    {
        if (empty($this->_bundleItems[$item['item_id']])) {
            /*push bundle data to bundle item list*/
            $this->_bundleItems[$item['item_id']] = $item;

            /*re calculate data for bundle children items*/
            $this->calculateDataForBundleChildrenItem($item, $allChildrenItems);
        }
    }

    /**
     * Re calculate data for bundle children item
     *
     * @param $item: parent item
     */
    public function calculateDataForBundleChildrenItem($item, $allChildrenItems = [])
    {
        /*get bundle children items*/
        if ($allChildrenItems) {
            $bundleChildrenItems = $allChildrenItems;
        } else {
            $bundleChildrenItems = $this->getBundleChildrenItemsById($item['item_id']);
        }

        if (!empty($bundleChildrenItems)) {
            /*bundle item - price*/
            $parentPrice = $item['price'];

            /*bundle item - row total*/
            $parentRowTotal = $item['row_total'];

            /*bundle item - tax riki*/
            $parentTaxRiki = $item['tax_riki'];

            /*bundle item - tax discount amount*/
            $parentDiscountAmount = $item['discount_amount'];

            /*bundle item - gift wrapping price*/
            $parentGiftWrappingPrice = $item['gw_price'];

            /*bundle item - discount_amount_excl_tax*/
            $parentDiscountAmountExclTax = $item['discount_amount_excl_tax'];

            /*bundle item - commission amount*/
            $parentCommissionAmount = $item['commission_amount'];

            /*flag to count total row_total for all of children item*/
            $totalChildrenItemRowTotal = 0;

            /*flag to count total price for all of children item*/
            $totalChildrenItemPrice = 0;

            /*bundle item - price_excl_tax*/
            $parentPriceInclTax = $item['price_incl_tax'];

            /*flag to count total tax riki for all of children item*/
            $totalChildrenItemTaxRiki = 0;

            /*flag to count total discount for all of children item*/
            $totalChildrenItemDiscountAmount = 0;

            /*flag to count total gift wrapping price for all of children item*/
            $totalChildrenItemGiftWrappingPrice = 0;

            /*flag to count total discount amount excl tax for all of children item*/
            $totalChildrenItemDiscountAmountExclTax = 0;

            /*flag to count total price incl tax for all of children item*/
            $totalChildrenItemPriceInclTax = 0;

            /*flag to count total commission amount for all of children item*/
            $totalChildrenItemCommissionAmount = 0;

            /*flag to store highest price value of children item*/
            $maxPrice = 0;

            /*flag to store item id which is highest price*/
            $maxPriceItemId = 0;

            if (isset($item['stock_point_applied_discount_rate']) && $item['stock_point_applied_discount_rate'] > 0) {
                $this->prepareAppliedStockPointDiscountItemsPrice($bundleChildrenItems, $item);
            }
            foreach ($bundleChildrenItems as $childrenItem) {
                /*bundle children item data after re calculated*/
                $itemData = [];

                /*get children item row total*/
                $childrenItemRowTotal = $this->getRowTotalForBundleChildrenItem($parentRowTotal, $childrenItem);

                if ($childrenItemRowTotal > $maxPrice) {
                    /*set max price again by this item price*/
                    $maxPrice = $childrenItemRowTotal;

                    /*set max price item id again by this item id*/
                    $maxPriceItemId = $childrenItem['item_id'];
                }

                /*children item - original price*/
                $price = $this->getOriginalPriceForBundleChildrenItem($childrenItem, $parentPrice);

                $itemData['price'] = $price;

                /*re-calculated row_total*/
                $itemData['row_total'] = $childrenItemRowTotal;

                /*sum children item - row total*/
                $totalChildrenItemRowTotal += $childrenItemRowTotal;

                /*sum children item - price*/
                $totalChildrenItemPrice += $price;

                /*re calculated tax_riki*/
                $itemTaxRiki = $this->getTaxAmountForBundleChildrenItem(
                    $parentTaxRiki,
                    $parentRowTotal,
                    $childrenItemRowTotal
                );

                /*sum children tax riki*/
                $totalChildrenItemTaxRiki += $itemTaxRiki;

                $itemData['tax_riki'] = $itemTaxRiki;

                /*re calculated discount_amount*/
                $itemDiscountAmount = $this->getDiscountAmountForBundleChildrenItem(
                    $parentDiscountAmount,
                    $parentRowTotal,
                    $childrenItemRowTotal
                );

                /*sum children discount amount*/
                $totalChildrenItemDiscountAmount += $itemDiscountAmount;

                $itemData['discount_amount'] = $itemDiscountAmount;

                /*re calculated gift wrapping fee (gw_price)*/
                $itemGwPrice = $this->getGwPriceForBundleChildrenItem(
                    $parentGiftWrappingPrice,
                    $parentRowTotal,
                    $childrenItemRowTotal
                );

                /*sum children gift wrapping fee*/
                $totalChildrenItemGiftWrappingPrice += $itemGwPrice;

                $itemData['gw_price'] = $itemGwPrice;

                /*re calculated discount amount excl tax for child item (discount_amount_excl_tax)*/
                $itemDiscountAmountExclTax = $this->getDiscountAmountExclTaxForBundleChildrenItem(
                    $parentDiscountAmountExclTax,
                    $parentDiscountAmount,
                    $itemDiscountAmount
                );

                /*sum children discount amount excl tax*/
                $totalChildrenItemDiscountAmountExclTax += $itemDiscountAmountExclTax;

                $itemData['discount_amount_excl_tax'] = $itemDiscountAmountExclTax;

                /*re calculated commission amount for child item (commission_amount)*/
                $itemCommissionAmount = $this->getCommissionAmountForBundleChildrenItem(
                    $parentCommissionAmount,
                    $parentRowTotal,
                    $childrenItemRowTotal
                );

                /*sum children commission amount*/
                $totalChildrenItemCommissionAmount += $itemCommissionAmount;

                $itemData['commission_amount'] = $itemCommissionAmount;

                /*re-calculated price include tax for child item (price_incl_tax)*/
                $defaultQty = $this->getDefaultQuantityForBundleChildrenItem($childrenItem);

                $itemData['default_qty'] = $defaultQty;

                /* if bundle product is gift-free product - price_incl_tax of child item is 0 */
                $itemData['price_incl_tax'] = (floatval($item['price_incl_tax']))
                    ? $this->getPriceIncludeTaxForBundleChildrenItem($price, $item['tax_percent'])
                    : 0;

                /*sum children price incl tax*/
                $totalChildrenItemPriceInclTax += $itemData['price_incl_tax'] * $defaultQty;

                /*push bundle children item data to list*/
                $this->_bundleChildrenItems[$childrenItem['item_id']] = $itemData;
            }

            if ($parentRowTotal && $parentRowTotal > $totalChildrenItemRowTotal) {
                /*sub row total will be sum for highest price item*/
                $subRowTotal = $parentRowTotal - $totalChildrenItemRowTotal;

                /*get highest price item*/
                if (!empty($this->_bundleChildrenItems[ $maxPriceItemId ])) {
                    /*sum sub tax for highest price item - column tax_riki*/
                    $this->_bundleChildrenItems[ $maxPriceItemId ]['row_total'] += $subRowTotal;
                }
            }

            if ($parentPrice && $parentPrice > $totalChildrenItemPrice) {
                /*sub price will be sum for highest price item*/
                $subPrice = $parentPrice - $totalChildrenItemPrice;

                /*get highest price item*/
                if (!empty($this->_bundleChildrenItems[ $maxPriceItemId ])) {
                    /*sum sub tax for highest price item - column tax_riki*/
                    $this->_bundleChildrenItems[ $maxPriceItemId ]['price'] += $subPrice;
                }
            }

            if ($parentTaxRiki && $parentTaxRiki > $totalChildrenItemTaxRiki) {
                /*sub tax will be sum for highest price item*/
                $subTax = $parentTaxRiki - $totalChildrenItemTaxRiki;

                /*get highest price item*/
                if (!empty($this->_bundleChildrenItems[ $maxPriceItemId ])) {
                    /*sum sub tax for highest price item - column tax_riki*/
                    $this->_bundleChildrenItems[ $maxPriceItemId ]['tax_riki'] += $subTax;
                }
            }

            if ($parentDiscountAmount && $parentDiscountAmount > $totalChildrenItemDiscountAmount) {
                /*sub discount will be sum for highest price item*/
                $subDiscount = $parentDiscountAmount - $totalChildrenItemDiscountAmount;

                if (!empty($this->_bundleChildrenItems[ $maxPriceItemId ])) {
                    $this->_bundleChildrenItems[ $maxPriceItemId ]['discount_amount'] += $subDiscount;
                }
            }

            if ($parentGiftWrappingPrice && $parentGiftWrappingPrice > $totalChildrenItemGiftWrappingPrice) {
                /*sub gift wrapping price will be sum for highest price item*/
                $subGwPrice = $parentGiftWrappingPrice - $totalChildrenItemGiftWrappingPrice;

                if (!empty($this->_bundleChildrenItems[ $maxPriceItemId ])) {
                    $this->_bundleChildrenItems[ $maxPriceItemId ]['gw_price'] += $subGwPrice;
                }
            }

            if ($parentDiscountAmountExclTax
                && $parentDiscountAmountExclTax > $totalChildrenItemDiscountAmountExclTax
            ) {
                /*sub discount amount excl tax will be sum for highest price item*/
                $subDiscountAmountExclTax = $parentDiscountAmountExclTax - $totalChildrenItemDiscountAmountExclTax;

                if (!empty($this->_bundleChildrenItems[ $maxPriceItemId ])) {
                    $this->_bundleChildrenItems[$maxPriceItemId]['discount_amount_excl_tax']
                        += $subDiscountAmountExclTax;
                }
            }

            if ($parentCommissionAmount && $parentCommissionAmount > $totalChildrenItemCommissionAmount) {
                /*sub commission amount will be sum for highest price item*/
                $subCommissionAmount = $parentCommissionAmount - $totalChildrenItemCommissionAmount;

                if (!empty($this->_bundleChildrenItems[ $maxPriceItemId ])) {
                    $this->_bundleChildrenItems[ $maxPriceItemId ]['commission_amount'] += $subCommissionAmount;
                }
            }

            if ($parentPriceInclTax && $parentPriceInclTax > $totalChildrenItemPriceInclTax) {
                /*sub price incl tax will be sum for highest price item*/
                $defaultQty = (!empty($this->_bundleChildrenItems[ $maxPriceItemId ]))
                    ? $this->_bundleChildrenItems[ $maxPriceItemId ]['default_qty'] : 0;

                if ($defaultQty) {
                    $subPriceInclTax = floor(($parentPriceInclTax - $totalChildrenItemPriceInclTax) / $defaultQty);
                } else {
                    $subPriceInclTax = 0;
                }

                if (!empty($this->_bundleChildrenItems[ $maxPriceItemId ])) {
                    $this->_bundleChildrenItems[ $maxPriceItemId ]['price_incl_tax'] += $subPriceInclTax;
                }
            }
        }
    }

    /**
     * Get bundle children items
     *
     * @param $itemId
     * @return array
     */
    public function getBundleChildrenItemsById($itemId)
    {
        /*sales order item table*/
        $orderItemTable = $this->_salesConnection->getTableName('sales_order_item');

        /*create sql query to get bundle children item*/
        $getBundleItemsQuery = $this->_salesConnection->select()->from(
            $orderItemTable
        )->where(
            $orderItemTable . '.parent_item_id = ?',
            $itemId
        );

        return $this->_salesConnection->fetchAll($getBundleItemsQuery);
    }

    /**
     * Get tax amount for bundle children item
     *
     * @param $parentTaxRiki
     * @param $parentRowTotal
     * @param $childrenRowTotal
     * @return float|int
     */
    public function getTaxAmountForBundleChildrenItem($parentTaxRiki, $parentRowTotal, $childrenRowTotal)
    {
        if ($parentTaxRiki && floatval($parentRowTotal) > 0) {
            return floor(
                $childrenRowTotal * $parentTaxRiki / $parentRowTotal
            );
        }

        return 0;
    }

    /**
     * Get discount amount for bundle children item
     *
     * @param $parentDiscountAmount
     * @param $parentRowTotal
     * @param $childrenRowTotal
     * @return float|int
     */
    public function getDiscountAmountForBundleChildrenItem($parentDiscountAmount, $parentRowTotal, $childrenRowTotal)
    {
        if ($parentDiscountAmount && floatval($parentRowTotal) > 0) {
            return floor(
                $childrenRowTotal * $parentDiscountAmount / $parentRowTotal
            );
        }

        return 0;
    }

    /**
     * Get Gift wrapping price for bundle children item
     *
     * @param $parentGiftWrappingPrice
     * @param $parentRowTotal
     * @param $childrenRowTotal
     * @return float|int
     */
    public function getGwPriceForBundleChildrenItem($parentGiftWrappingPrice, $parentRowTotal, $childrenRowTotal)
    {
        if ($parentGiftWrappingPrice && floatval($parentRowTotal) > 0) {
            return floor(
                $childrenRowTotal * $parentGiftWrappingPrice / $parentRowTotal
            );
        }

        return 0;
    }

    /**
     * Get row total for bundle children item
     *
     * @param $childrenTaxRiki
     * @param $parentRowTotal
     * @param $childrenRowTotal
     * @return int
     */
    public function getRowTotalInclTaxForBundleChildrenItem($childrenTaxRiki, $parentRowTotal, $childrenRowTotal)
    {
        if ($parentRowTotal && floatval($parentRowTotal) > 0) {
            return $childrenRowTotal + $childrenTaxRiki;
        }

        return 0;
    }

    /**
     * Get row total for bundle children item
     *
     * @param $parentRowTotal
     * @param $item
     * @return float|int
     */
    public function getRowTotalForBundleChildrenItem($parentRowTotal, $item)
    {
        if ($parentRowTotal > 0) {
            /*get product option*/
            if (is_array($item['product_options'])) {
                $productOption = $item['product_options'];
            } else {
                $productOption = $this->unserializeOption($item['product_options']);
            }
            if ($productOption && !empty($productOption['bundle_selection_attributes'])) {
                /*get bundle option*/
                $bundleOption = $this->unserializeOption($productOption['bundle_selection_attributes']);
                if ($bundleOption) {
                    $price = isset($this->appliedStockPointDiscountItemsPrice[$item['item_id']])
                        ? $this->appliedStockPointDiscountItemsPrice[$item['item_id']]
                        : $bundleOption['price'];
                    if (!empty($bundleOption['qty'])) {
                        return $price / $bundleOption['qty'] * $item['qty_ordered'];
                    } else {
                        return $price * $item['qty_ordered'];
                    }
                }
            }
        }
        return 0;
    }

    /**
     * Get original price for bundle item
     *
     * @param $item
     * @return int
     */
    private function _getOriginalPriceForBundleItem($item)
    {
        $originalPrice = 0;

        $productOption = $this->unserializeOption($item['product_options']);

        if ($productOption && !empty($productOption['bundle_options'])) {
            foreach ($productOption['bundle_options'] as $options) {
                if (!empty($options['value'])) {
                    foreach ($options['value'] as $productInfo) {
                        $productPrice = !empty($productInfo['price'])
                                        ? floor($productInfo['price'])
                                        : 0;

                        $originalPrice += $productPrice;
                    }
                }
            }
        }

        return $originalPrice;
    }

    /**
     * Get original price for bundle children item
     *
     * @param $item
     * @param $parentPrice
     * @return float|int
     */
    public function getOriginalPriceForBundleChildrenItem($item, $parentPrice)
    {
        if ($parentPrice > 0) {
            /*get product option*/
            $productOption = $this->unserializeOption($item['product_options']);
            if ($productOption && !empty($productOption['bundle_selection_attributes'])) {
                /*get bundle option*/
                $bundleOption = $this->unserializeOption($productOption['bundle_selection_attributes']);
                if ($bundleOption) {
                    $price = isset($this->appliedStockPointDiscountItemsPrice[$item['item_id']])
                        ? $this->appliedStockPointDiscountItemsPrice[$item['item_id']]
                        : $bundleOption['price'];
                    if (!empty($bundleOption['qty'])) {
                        return $price / $bundleOption['qty'];
                    } else {
                        return $price;
                    }
                }
            }
        }
        return 0;
    }

    /**
     * Get price for bundle children item
     *
     * @param $parentPrice
     * @param $parentOriginalPrice
     * @param $itemOriginalPrice
     * @return float|int
     */
    private function _getPriceForBundleChildrenItem($parentPrice, $parentOriginalPrice, $itemOriginalPrice)
    {
        if ($parentPrice && (float)($parentOriginalPrice) > 0) {
            return floor(
                $parentPrice * $itemOriginalPrice / $parentOriginalPrice
            );
        }

        return 0;
    }

    /**
     * Get discount amount excl tax for bundle children item
     *
     * @param $parentDiscountAmountExclTax
     * @param $parentDiscountAmount
     * @param $childrenDiscountAmount
     * @return float|int
     */
    public function getDiscountAmountExclTaxForBundleChildrenItem($parentDiscountAmountExclTax, $parentDiscountAmount, $childrenDiscountAmount)
    {
        if ($parentDiscountAmountExclTax && floatval($parentDiscountAmount) > 0) {
            return floor(
                $childrenDiscountAmount * $parentDiscountAmountExclTax / $parentDiscountAmount
            );
        }

        return 0;
    }

    /**
     * Get commission amount for bundle children item
     *
     * @param $parentCommissionAmount
     * @param $parentRowTotal
     * @param $childrenItemRowTotal
     * @return float|int
     */
    public function getCommissionAmountForBundleChildrenItem($parentCommissionAmount, $parentRowTotal, $childrenItemRowTotal)
    {
        if ($parentCommissionAmount && floatval($parentRowTotal) > 0) {
            return floor(
                $childrenItemRowTotal * $parentCommissionAmount / $parentRowTotal
            );
        }

        return 0;
    }

    /**
     * Get order item by
     * @param $itemId
     * @return array
     */
    public function getOrderItemById($itemId)
    {
        /*sales order item table*/
        $orderItemTable = $this->_salesConnection->getTableName('sales_order_item');

        /*create sql query to get bundle children item*/
        $getBundleItemsQuery = $this->_salesConnection->select()->from(
            $orderItemTable
        )->where(
            $orderItemTable . '.item_id = ?',
            $itemId
        );

        return $this->_salesConnection->fetchRow($getBundleItemsQuery);
    }

    /**
     * un serialize Option
     *
     * @param $option
     * @return bool|mixed
     */
    public function unserializeOption($option)
    {
        try {
            return $this->serializer->unserialize($option);
        } catch (\Exception $e) {
            $this->_logger->critical($e->getMessage());
        }
        return false;
    }

    /**
     * Get default quantity for bundle children item
     *
     * @param $item
     * @return int
     */
    public function getDefaultQuantityForBundleChildrenItem($item)
    {
        /*get product option*/
        $productOption = $this->unserializeOption($item['product_options']);
        if ($productOption && !empty($productOption['bundle_selection_attributes'])) {
            /*get bundle option*/
            $bundleOption = $this->unserializeOption($productOption['bundle_selection_attributes']);
            if ($bundleOption) {
                if (!empty($bundleOption['qty'])) {
                    return $bundleOption['qty'];
                }
            }
        }

        return 0;
    }

    /**
     * @param string $productOptions
     * @return string $formattedOptions|$productOptions
     */
    public function convertDetailOptionsToJsonFormat($productOptions)
    {
        $productOptionsArray = $this->unserializeOption($productOptions);

        if ($productOptionsArray !== false) {

            // NED-3946 - In case product_options is over encoded, web will unserialize it one more
            if(!is_array($productOptionsArray)) {
                $productOptionsArray = $this->unserializeOption($productOptionsArray);
            }

            if (!empty($productOptionsArray['bundle_selection_attributes'])) {
                try {
                    $productOptionsArray['bundle_selection_attributes'] = $this->unserializeOption($productOptionsArray['bundle_selection_attributes']);
                } catch (\InvalidArgumentException $e) {
                }
            }

            $formattedOptions = $this->serializer->serialize($productOptionsArray);

            return $formattedOptions;
        }

        return $productOptions;
    }

    /**
     * Get price include tax for bundle children item
     *
     * @param $price
     * @param $taxPercent
     * @return float|int
     */
    public function getPriceIncludeTaxForBundleChildrenItem($price, $taxPercent)
    {
        if ($taxPercent) {
            $taxPercent = $taxPercent / 100;

            return floor($price * (1 + $taxPercent));
        }

        return $price;
    }

    /**
     * @param integer $stockPointDiscountRate
     * @param integer $price
     * @return integer
     */
    public function getAppliedStockPointDiscountAmountPrice($stockPointDiscountRate, $price)
    {
        if ($price > 0) {
            $discountAmount = $price * ((int)$stockPointDiscountRate / 100);
            return $price - floor($discountAmount);
        }
        return 0;
    }

    /**
     * @param array $bundleChildrenItems
     * @param array $parentItem
     * @return array
     */
    protected function prepareAppliedStockPointDiscountItemsPrice($bundleChildrenItems, $parentItem)
    {
        $maxPriceItemId = 0;
        $maxPrice = 0;
        $totalBundlePrice = 0;
        $itemsPrice = [];
        foreach ($bundleChildrenItems as $childrenItem) {
            $productOption = $this->unserializeOption($childrenItem['product_options']);
            if ($productOption && !empty($productOption['bundle_selection_attributes'])) {
                /*get bundle option*/
                $bundleOption = $this->unserializeOption($productOption['bundle_selection_attributes']);
                if ($bundleOption) {
                    if (isset($bundleOption['price']) && $bundleOption['price'] > 0) {
                        if ($bundleOption['price'] > $maxPrice) {
                            $maxPrice = $bundleOption['price'];
                            $maxPriceItemId = $childrenItem['item_id'];
                        }
                        $appliedStockPointDiscountPrice = $this->getAppliedStockPointDiscountAmountPrice(
                            $parentItem['stock_point_applied_discount_rate'],
                            $bundleOption['price']
                        );
                        $itemsPrice[$childrenItem['item_id']] = $appliedStockPointDiscountPrice;
                        $totalBundlePrice += $appliedStockPointDiscountPrice;
                        if ($totalBundlePrice - $parentItem['price'] > 0) {
                            $itemsPrice[$maxPriceItemId] -= ($totalBundlePrice - $parentItem['price']);
                        }
                    }
                }
            }
        }
        $this->appliedStockPointDiscountItemsPrice = $itemsPrice;
    }
}
