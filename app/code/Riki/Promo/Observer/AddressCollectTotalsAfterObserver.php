<?php
namespace Riki\Promo\Observer;


/**
 * Revert 'deleted' status and auto add all simple products without required options
 */

class AddressCollectTotalsAfterObserver  extends \Amasty\Promo\Observer\AddressCollectTotalsAfterObserver
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

    /**
     * @var \Magento\Framework\Event\Manager
     */
    protected $eventManager;

    /**
     * AddressCollectTotalsAfterObserver constructor.
     *
     * @param \Magento\Framework\Event\Manager $eventManager
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Amasty\Promo\Helper\Item $promoItemHelper
     * @param \Amasty\Promo\Model\Registry $promoRegistry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\Event\Manager $eventManager,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Amasty\Promo\Helper\Item $promoItemHelper,
        \Amasty\Promo\Model\Registry $promoRegistry,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        $this->eventManager = $eventManager;
        parent::__construct($registry,$productFactory,$promoItemHelper,$promoRegistry,$scopeConfig);
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

            $changedItems = $this->_coreRegistry->registry('ampromo_changed_items');
            $this->_coreRegistry->unregister('ampromo_changed_items');

            /** @var \Magento\Quote\Model\Quote\Item $item */
            foreach ($items as $item)
            {
                $sku = $item->getProduct()->getData('sku');

                if (!isset($toAdd[$sku]))
                    continue;

                if ($this->promoItemHelper->isPromoItem($item)){
                    $toAdd[$sku]['qty'] -= $item->getQty();

                    if($toAdd[$sku]['qty'] != 0){
                        if(!is_array($changedItems)){
                            $changedItems = [];
                        }

                        $changedItems[] = $item->getId();
                    }

                }

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
                            'rule_id'   =>  $item['rule_id'],
                            'qty'     => $item['qty']
                        ];
                    }
                } elseif ($item['qty'] > 0 && !isset($deleted[$sku])) {
                    $product = $this->_productFactory->create()->loadByAttribute('sku', $sku);
                    $this->eventManager->dispatch(\Riki\AdvancedInventory\Observer\OosCapture::EVENT, [
                        'quote' => $quote,
                        'product' => $product,
                        'qty' => $item['qty'],
                        'salesrule_id' => $item['rule_id'],
                    ]);
                }
            }

            $this->_coreRegistry->register('ampromo_changed_items', $changedItems);
            $this->_coreRegistry->register('ampromo_to_add', $collectorData);
        }
    }
}