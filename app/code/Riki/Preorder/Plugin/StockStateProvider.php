<?php

namespace Riki\Preorder\Plugin;

class StockStateProvider
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Riki\Preorder\Helper\Data
     */
    protected $helper;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Riki\Preorder\Helper\Data $helper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
    }

    /*
    public function aroundCheckQty(\Magento\CatalogInventory\Model\StockStateProvider $subject,\Closure $closure,\Magento\CatalogInventory\Api\Data\StockItemInterface $stockItem, $qty)
    {
        $result = $closure($stockItem, $qty);
        if ($result) {
            return $result;
        }

        return $this->helper->checkCanPreOrder($stockItem->getProductId());
    }

    public function aroundVerifyStock
    (
        \Magento\CatalogInventory\Model\StockStateProvider $subject,
        \Closure $closure,
        \Magento\CatalogInventory\Api\Data\StockItemInterface $stockItem
    ){
        $result = $closure($stockItem);
        if(!$result){
            return $result;
        }

        if ($stockItem->getQty() <= $stockItem->getMinQty() && $stockItem->getBackorders() == \Riki\Preorder\Helper\Data::BACKORDERS_PREORDER_OPTION) {
            return $this->scopeConfig->isSetFlag('rikipreorder/functional/allowemptyqty', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }

        return true;
    }
    */
}
