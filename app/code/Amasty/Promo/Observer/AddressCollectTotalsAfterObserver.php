<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_Promo
 */


namespace Amasty\Promo\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Revert 'deleted' status and auto add all simple products without required options
 */

class AddressCollectTotalsAfterObserver implements ObserverInterface
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    /**
     * @var \Amasty\Promo\Helper\Item
     */
    protected $promoItemHelper;

    /**
     * @var \Amasty\Promo\Model\Registry
     */
    protected $promoRegistry;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Amasty\Promo\Helper\Item $promoItemHelper,
        \Amasty\Promo\Model\Registry $promoRegistry,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->_coreRegistry = $registry;
        $this->_productFactory = $productFactory;
        $this->promoItemHelper = $promoItemHelper;
        $this->promoRegistry = $promoRegistry;
        $this->scopeConfig = $scopeConfig;
    }


    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $observer->getQuote();

        $items = $quote->getAllItems();

        $addAutomatically = $this->scopeConfig->isSetFlag(
            'ampromo/general/auto_add',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if ($addAutomatically)
        {
            $toAdd  = $this->promoRegistry->getPromoItems();
            unset($toAdd['_groups']);

            foreach ($items as $item)
            {
                $sku = $item->getProduct()->getData('sku');

                if (!isset($toAdd[$sku]))
                    continue;

                if ($this->promoItemHelper->isPromoItem($item))
                    $toAdd[$sku]['qty'] -= $item->getQty();
            }

            $deleted = $this->promoRegistry->getDeletedItems();

            $this->_coreRegistry->unregister('ampromo_to_add');
            $collectorData = [];

            foreach ($toAdd as $sku => $item) {
                if ($item['qty'] > 0 && $item['auto_add'] && !isset($deleted[$sku])) {
                    $product = $this->_productFactory->create()->loadByAttribute('sku', $sku);

                    if (isset($collectorData[$product->getId()])) {
                        $collectorData[$product->getId()]['qty'] += $item['qty'];
                    }
                    else {
                        $collectorData[$product->getId()] = [
                            'product' => $product,
                            'qty'     => $item['qty']
                        ];
                    }
                }
            }

            $this->_coreRegistry->register('ampromo_to_add', $collectorData);
        }
    }
}
