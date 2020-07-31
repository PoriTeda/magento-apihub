<?php

namespace Riki\CatalogRule\Observer;

class ProcessAdminFinalPriceObserver extends \Magento\CatalogRule\Observer\ProcessAdminFinalPriceObserver
{
    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $backendQuoteSession;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * ProcessAdminFinalPriceObserver constructor.
     *
     * @param \Magento\Backend\Model\Session\Quote $backendQuoteSession
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\CatalogRule\Observer\RulePricesStorage $rulePricesStorage
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\CatalogRule\Model\ResourceModel\RuleFactory $resourceRuleFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     */
    public function __construct(
        \Magento\Backend\Model\Session\Quote $backendQuoteSession,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\CatalogRule\Observer\RulePricesStorage $rulePricesStorage,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\CatalogRule\Model\ResourceModel\RuleFactory $resourceRuleFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
    )
    {
        $this->backendQuoteSession = $backendQuoteSession;
        $this->customerRepository = $customerRepository;
        $this->storeManager = $storeManager;
        parent::__construct($rulePricesStorage, $coreRegistry, $resourceRuleFactory, $localeDate);
    }

    /**
     * @inheritdoc
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Riki\CatalogRule\Model\ResourceModel\Rule $ruleResource */
        $ruleResource = $this->resourceRuleFactory->create();

        list($courseId, $frequencyId, $nDelivery, $subscriptionProfile) = $ruleResource->getSubscriptionProfileInfoFromRequest();

        try {
            if ($subscriptionProfile) {
                $customer = $this->customerRepository->getById($subscriptionProfile->getCustomerId());
            } elseif ($customerId = $this->backendQuoteSession->getCustomerId()) {
                $customer = $this->customerRepository->getById($customerId);
            }
        } catch (\Exception $e) {
            $customer = null;
        }

        $product = $observer->getEvent()->getProduct();
        $storeId = $product->getStoreId();
        $date = $this->localeDate->scopeDate($storeId);
        $key = false;

        if ($courseId && $frequencyId && !$this->coreRegistry->registry('rule_data')) {
            $wId = null;
            $gId = null;
            if ($observer->hasWebsiteId()) {
                $wId = $observer->getEvent()->getWebsiteId();
            } else {
                $wId = $this->storeManager->getStore($storeId)->getWebsiteId();
            }

            if ($observer->hasCustomerGroupId()) {
                $gId = $observer->getEvent()->getCustomerGroupId();
            } elseif ($product->hasCustomerGroupId()) {
                $gId = $product->getCustomerGroupId();
            } elseif (isset($customer) && $customer->getId()) {
                $gId = $customer->getGroupId();
            }

            if ($wId && $gId) {
                $this->coreRegistry->register('rule_data', new \Magento\Framework\DataObject(
                    [
                        'store_id' => $storeId,
                        'website_id' => $wId,
                        'customer_group_id' => $gId,
                    ]
                ));
            }
        }

        $ruleData = $this->coreRegistry->registry('rule_data');
        if ($ruleData) {
            $wId = $ruleData->getWebsiteId();
            $gId = $ruleData->getCustomerGroupId();
            $pId = $product->getId();

            $key = "{$date->format('Y-m-d H:i:s')}|{$wId}|{$gId}|{$pId}|{$courseId}|{$frequencyId}|{$nDelivery}";
        } elseif ($product->getWebsiteId() !== null && $product->getCustomerGroupId() !== null) {
            $wId = $product->getWebsiteId();
            $gId = $product->getCustomerGroupId();
            $pId = $product->getId();
            $key = "{$date->format('Y-m-d H:i:s')}|{$wId}|{$gId}|{$pId}|{$courseId}|{$frequencyId}|{$nDelivery}";
        }

        $product->setRulePrice(null);

        if ($key) {
            if (!$this->rulePricesStorage->hasRulePrice($key)) {
                $rulePrice = $this->resourceRuleFactory->create()->getRulePrice($date, $wId, $gId, $pId);
                $this->rulePricesStorage->setRulePrice($key, $rulePrice);
            }

            if ($this->rulePricesStorage->getRulePrice($key) !== false) {
                $originalFinalPrice = $product->getData('final_price');
                $rulePrice = $this->rulePricesStorage->getRulePrice($key);
                $finalPrice = min($originalFinalPrice, $rulePrice);
                $product->setFinalPrice($finalPrice);

                if ($rulePrice <= $originalFinalPrice) {
                    $product->setRulePrice($rulePrice);
                }
            }
        }

        return $this;
    }
}
