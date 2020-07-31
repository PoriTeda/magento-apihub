<?php

namespace Riki\PageCache\Observer;

class ValidateProductStockAfterAssigned implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Riki\PageCache\Indexer\CacheContext
     */
    protected $cacheContext;
    /**
     * @var \Riki\AdvancedInventory\Helper\Assignation
     */
    protected $assignationHelper;

    public function __construct(
        \Magento\PageCache\Model\Config $config,
        \Riki\PageCache\Indexer\CacheContext $cacheContext,
        \Riki\AdvancedInventory\Helper\Assignation $assignationHelper
    ) {
        $this->_config = $config;
        $this->cacheContext = $cacheContext;
        $this->assignationHelper = $assignationHelper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $stockData = $observer->getEvent()->getData('stockData');
        $stockAssign = $observer->getEvent()->getData('stockAssign');

        if (empty($stockData) || empty($stockAssign)) {
            return;
        }

        $afterAssign = [];

        $productList = [];

        foreach ($stockData as $stockItem) {

            $productId = $stockItem['product_id'];

            if (!in_array($productId, $productList)) {
                array_push($productList, $productId);
            }

            $newStockItem = $stockItem;

            /*product has been assigned*/
            if (!empty($stockAssign[$productId])) {

                $placeId = $stockItem['place_id'];

                /*product has been assigned for current places*/
                if (!empty($stockAssign[$productId][$placeId])) {
                    $newStockItem['quantity_in_stock'] += $stockAssign[$productId][$placeId];
                }
            }

            array_push($afterAssign, $newStockItem);
        }

        $productListNeedClearCache = [];

        foreach ($productList as $productId) {

            $statusBeforeAssign = $this->getProductStatus($productId,$stockData);

            $statusAfterAssign = $this->getProductStatus($productId,$afterAssign);

            if ($statusBeforeAssign != $statusAfterAssign) {
                if (!in_array($productId, $productListNeedClearCache)) {
                    array_push($productListNeedClearCache, $productId);
                }
            } else {
                $defaultPos = $this->assignationHelper->getDefaultPosForFo();

                $statusBeforeAssignDefaultPos = $this->getProductStatus($productId,$stockData, $defaultPos);

                $statusAfterAssignDefaultPos = $this->getProductStatus($productId,$afterAssign, $defaultPos);

                if ($statusBeforeAssignDefaultPos != $statusAfterAssignDefaultPos) {
                    if (!in_array($productId, $productListNeedClearCache)) {
                        array_push($productListNeedClearCache, $productId);
                    }
                }
            }
        }

        if (!empty($productListNeedClearCache)) {
            $this->cacheContext->registerEntities(
                \Magento\Catalog\Model\Product::CACHE_TAG,
                $productListNeedClearCache
            );
            return true;
        }

        return false;
    }

    /**
     * Get product status
     *
     * @param $productId
     * @param $stockData
     * @param bool|array $placeIds
     * @return bool
     */
    public function getProductStatus($productId, $stockData, $placeIds = false)
    {
        $qty = 0;
        foreach ($stockData as $item) {
            if ($item['product_id'] == $productId) {
                if (empty($placeId) || in_array($item['place_id'], $placeIds)) {
                    if ($item['manage_stock'] == 1) {
                        $qty += (int)$item['quantity_in_stock'];
                        if ($item['backorder_allowed'] == 1) {
                            $qty += (int)$item['backorder_limit'];
                        }
                    }
                }
            }
        }

        if ($qty <= 0) {
            /*out of stock*/
            return false;
        }

        return true;
    }
}
