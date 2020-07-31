<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_Promo
 */

namespace Amasty\Promo\Model;


class Registry extends \Magento\Framework\Model\AbstractModel
{
    protected $_hasItems = false;
    protected $_locked = false;
    protected $_isHandled = [];

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Amasty\Promo\Helper\Item
     */
    protected $promoItemHelper;

    /**
     * @var \Amasty\Promo\Helper\Messages
     */
    protected $promoMessagesHelper;

    public function __construct(
        \Magento\Checkout\Model\Session $resourceSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Amasty\Promo\Helper\Item $promoItemHelper,
        \Amasty\Promo\Helper\Messages $promoMessagesHelper
    ) {
        $this->_checkoutSession = $resourceSession;
        $this->scopeConfig = $scopeConfig;
        $this->_productFactory = $productFactory;
        $this->_storeManager = $storeManager;
        $this->promoItemHelper = $promoItemHelper;
        $this->promoMessagesHelper = $promoMessagesHelper;
    }

    public function getApplyAttempt($ruleId)
    {
        if (isset($this->_isHandled[$ruleId])) {
            return false;
        }
        else {
            $this->_isHandled[$ruleId] = true;

            return true;
        }
    }

    public function addPromoItem($sku, $qty, $ruleId)
    {
        if ($this->_locked)
            return;

        if (!$this->_hasItems)
            $this->reset();

        $this->_hasItems = true;


        $items = $this->_checkoutSession->getAmpromoItems();
        if ($items === null)
            $items = ['_groups' => []];

        $autoAdd = false;

        $addAutomatically = $this->scopeConfig->isSetFlag(
            'ampromo/general/auto_add',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if (!is_array($sku)) {
            if ($addAutomatically) {

                $collection = $this->_productFactory->create()->getCollection()
                    ->addAttributeToSelect(['name', 'status', 'required_options', 'allow_spot_order'])
                    ->addAttributeToFilter('sku', $sku)
                    ->setPage(1, 1)
                ;

                $product = $collection->getFirstItem();

                $currentWebsiteId = $this->_storeManager->getWebsite()->getId();
                if (!is_array($product->getWebsiteIds())
                    || !in_array($currentWebsiteId, $product->getWebsiteIds())){
                    // Ignore products from other websites
                    return;
                }

                if (!$product || !$product->isInStock() || !$product->isSalable()) {
                    $this->promoMessagesHelper->addAvailabilityError($product);
                } else {
                    if (in_array($product->getTypeId(), ['simple', 'virtual'])
                        && !$product->getTypeInstance(true)->hasRequiredOptions($product)) {
                        $autoAdd = true;
                    }
                }
            }

            if (isset($items[$sku])) {
                $items[$sku]['qty'] += $qty;
            } else {
                $items[$sku] = [
                    'sku' => $sku,
                    'qty' => $qty,
                    'auto_add' => $autoAdd
                ];
            }
        }
        else {
            $items['_groups'][$ruleId] = [
                'sku' => $sku,
                'qty' => $qty
            ];
        }

        $this->_checkoutSession->setAmpromoItems($items);
    }

    public function getPromoItems()
    {
        $items = $this->_checkoutSession->getAmpromoItems();

        return $items ? $items : ['_groups' => []];
    }

    public function reset()
    {
        if ($this->_hasItems) {
            $this->_locked = true;
            return;
        }

        $this->_checkoutSession->setAmpromoItems(['_groups' => []]);
    }

    public function getLimits()
    {
        $quote = $this->_checkoutSession->getQuote();

        $allowed = $this->getPromoItems();

        foreach ($quote->getAllItems() as $item)
        {
            $sku = $item->getProduct()->getData('sku');

            if ($this->promoItemHelper->isPromoItem($item)) {
                $ruleId = $this->promoItemHelper->getRuleId($item);

                if (isset($allowed['_groups'][$ruleId]))
                {
                    if ($item->getParentItem())
                        continue;

                    $allowed['_groups'][$ruleId]['qty'] -= $item->getQty();
                    if ($allowed['_groups'][$ruleId]['qty'] <= 0)
                        unset($allowed['_groups'][$ruleId]);
                }
                else if (isset($allowed[$sku])) {
                    $allowed[$sku]['qty'] -= $item->getQty();

                    if ($allowed[$sku]['qty'] <= 0)
                        unset($allowed[$sku]);
                }
            }
        }

        return $allowed;
    }

    public function deleteProduct($sku)
    {
        $deletedItems = $this->_checkoutSession->getAmpromoDeletedItems();

        if (!$deletedItems)
            $deletedItems = [];

        $deletedItems[$sku] = true;

        $this->_checkoutSession->setAmpromoDeletedItems($deletedItems);
    }

    public function restore($sku)
    {
        $deletedItems = $this->_checkoutSession->getAmpromoDeletedItems();

        if (!$deletedItems || !isset($deletedItems[$sku]))
            return;

        unset($deletedItems[$sku]);

        $this->_checkoutSession->setAmpromoDeletedItems($deletedItems);
    }

    public function getDeletedItems()
    {
        $deletedItems = $this->_checkoutSession->getAmpromoDeletedItems();

        if (!$deletedItems)
            $deletedItems = [];

        return $deletedItems;
    }

    /**
     * Check this product sku is able to add cart as free gift
     *
     * @param string $sku
     * @param boolean $auto
     * @return boolean
     */
    public function isAbleToAdd($sku, $auto = false)
    {
        $collection = $this->_productFactory->create()->getCollection()
            ->addAttributeToSelect(['name', 'status', 'required_options', 'allow_spot_order'])
            ->addAttributeToFilter('sku', $sku)
            ->setPage(1, 1)
        ;

        $product = $collection->getFirstItem();

        $currentWebsiteId = $this->_storeManager->getWebsite()->getId();
        if (!is_array($product->getWebsiteIds())
            || !in_array($currentWebsiteId, $product->getWebsiteIds())){
            // Ignore products from other websites
            return false;
        }

        if (!$product || !$product->isInStock() || !$product->isSalable() || ($auto && $product->getTypeId() !== 'simple')) {
            return false;
        }
        return true;
    }

}
