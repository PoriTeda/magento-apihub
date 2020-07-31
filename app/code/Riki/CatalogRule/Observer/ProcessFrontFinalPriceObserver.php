<?php

namespace Riki\CatalogRule\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Model\Product;
use Magento\CatalogRule\Model\Rule;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Customer\Model\Session as CustomerModelSession;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\CatalogRule\Observer\RulePricesStorage;

class ProcessFrontFinalPriceObserver extends \Magento\CatalogRule\Observer\ProcessFrontFinalPriceObserver
{

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    public function __construct(
        \Magento\Framework\Registry $registry,
        RulePricesStorage $rulePricesStorage,
        \Magento\CatalogRule\Model\ResourceModel\RuleFactory $resourceRuleFactory,
        StoreManagerInterface $storeManager,
        TimezoneInterface $localeDate,
        CustomerModelSession $customerSession
    ) {
        $this->_registry = $registry;
        parent::__construct($rulePricesStorage, $resourceRuleFactory, $storeManager, $localeDate, $customerSession);
    }

    /**
     * Apply catalog price rules to product on frontend
     * Note: category page does not trigger this observer anymore because we cached data in getRulePrice function
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();
        $pId = $product->getId();
        $storeId = $product->getStoreId();

        if ($observer->hasDate()) {
            $date = new \DateTime($observer->getEvent()->getDate());
        } else {
            $date = $this->localeDate->scopeDate($storeId);
        }

        if ($observer->hasWebsiteId()) {
            $wId = $observer->getEvent()->getWebsiteId();
        } else {
            $wId = $this->storeManager->getStore($storeId)->getWebsiteId();
        }

        if ($observer->hasCustomerGroupId()) {
            $gId = $observer->getEvent()->getCustomerGroupId();
        } elseif ($product->hasCustomerGroupId()) {
            $gId = $product->getCustomerGroupId();
        } else {
            $gId = $this->customerSession->getCustomerGroupId();
        }

        $key = "{$date->format('Y-m-d H:i:s')}|{$wId}|{$gId}|{$pId}";

        if ($isSimulator = $this->_registry->registry('is_simulator')) {
            $key .= "|{$isSimulator}";
        }

        if ($nthDelivery = $this->_registry->registry('nth_delivery_override')) {
            $key .= "|{$nthDelivery}";
        }

        if (!$this->rulePricesStorage->hasRulePrice($key)) {
            $rulePrice = $this->resourceRuleFactory->create()->getRulePrice($date, $wId, $gId, $pId);
            $this->rulePricesStorage->setRulePrice($key, $rulePrice);
        }

        $product->setRulePrice(null);

        if ($this->rulePricesStorage->getRulePrice($key) !== false) {
            $originalFinalPrice = $product->getData('final_price');
            $rulePrice = $this->rulePricesStorage->getRulePrice($key);
            $finalPrice = min($originalFinalPrice, $rulePrice);
            $product->setFinalPrice($finalPrice);

            if($rulePrice <= $originalFinalPrice){
                $product->setRulePrice($rulePrice);
            }
        }
        return $this;
    }
}