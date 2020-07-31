<?php

namespace Riki\Promo\Observer;

class CatalogProductIsSalableAfter extends \Riki\AdvancedInventory\Observer\CatalogProductIsSalableAfter
{
    /**
     * @var \Magento\Framework\UrlInterface
     */
    private $url;

    /**
     * CatalogProductIsSalableAfter constructor.
     * @param \Magento\CatalogInventory\Model\StockRegistry $stockRegistry
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Wyomind\AdvancedInventory\Model\Stock $modelStock
     * @param \Wyomind\Core\Helper\Data $coreHelperData
     * @param \Wyomind\PointOfSale\Model\PointOfSale $modelPointOfSale
     * @param \Riki\AdvancedInventory\Helper\Assignation $assignationHelper
     * @param \Riki\Catalog\Model\StockState $stockState
     * @param \Magento\Framework\UrlInterface $url
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Framework\App\State $state
     */
    public function __construct(
        \Magento\CatalogInventory\Model\StockRegistry $stockRegistry,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Wyomind\AdvancedInventory\Model\Stock $modelStock,
        \Wyomind\Core\Helper\Data $coreHelperData,
        \Wyomind\PointOfSale\Model\PointOfSale $modelPointOfSale,
        \Riki\AdvancedInventory\Helper\Assignation $assignationHelper,
        \Riki\Catalog\Model\StockState $stockState,
        \Magento\Framework\UrlInterface $url,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Framework\App\State $state
    ) {
        $this->url = $url;

        parent::__construct(
            $stockRegistry,
            $storeManager,
            $modelStock,
            $coreHelperData,
            $modelPointOfSale,
            $assignationHelper,
            $stockState,
            $request,
            $productFactory,
            $state
        );
    }

    /**
     * Need to apply stock point display rule for Rest API be requested from checkout page
     * Only apply for 3 checkout urls to avoid impact to other APIs, those be not request from checkout page
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $checkoutUrls = [
            'rikicarts/mine/payment-information', //checkout page
            'carts/mine/payment-information', // confirm page
            'riki-loyalty/:cartId/apply-reward-point' // checkout-page
        ];

        foreach ($checkoutUrls as $url) {
            if (strpos($this->url->getCurrentUrl(), $url) !== false) {
                return parent::execute($observer);
            }
        }
    }
}
