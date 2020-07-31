<?php

namespace Riki\CatalogRule\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class PrepareProductIdsForPriceCalculation implements ObserverInterface
{
    /**
     * @var \Magento\CatalogRule\Observer\RulePricesStorage
     */
    protected $rulePricesStorage;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var \Riki\CatalogRule\Helper\Data
     */
    protected $dataHelper;

    /**
     * PrepareProductIdsForPriceCalculation constructor.
     *
     * @param \Magento\CatalogRule\Observer\RulePricesStorage $rulePricesStorage
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Riki\CatalogRule\Helper\Data $dataHelper
     */
    public function __construct(
        \Magento\CatalogRule\Observer\RulePricesStorage $rulePricesStorage,
        \Magento\Framework\Registry $coreRegistry,
        \Riki\CatalogRule\Helper\Data $dataHelper
    )
    {
        $this->rulePricesStorage = $rulePricesStorage;
        $this->coreRegistry = $coreRegistry;
        $this->dataHelper = $dataHelper;
    }

    /**
     * @param Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $collection = $observer->getEvent()->getCollection();

        $productIds = [];
        /* @var $product Product */
        foreach ($collection as $product) {
            $productIds[] = $product->getId();
        }

        if ($productIds) {
            $this->dataHelper->registerPreLoadedProductIds($productIds);
        }
    }
}
