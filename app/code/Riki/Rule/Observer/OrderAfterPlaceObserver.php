<?php

namespace Riki\Rule\Observer;

use Magento\Framework\Event\ObserverInterface;
use Riki\Sales\Model\ResourceModel\OrderColor;
use Amasty\Promo\Helper\Item as promoItemHelper;
use Magento\Framework\Registry;
use Riki\Rule\Model\CumulatedGift;

class OrderAfterPlaceObserver implements ObserverInterface
{
    const ORDER_SUBSCRIPTION = 'SUBSCRIPTION';

    const ORDER_HANPUKAI = 'HANPUKAI';

    /**
     * Order SAP Booking.
     *
     * @var \Riki\Rule\Model\OrderSapBookingFactory OrderSapBookingFactory
     */
    protected $orderSapBookingFactory;

    /**
     * SalesRule factory.
     *
     * @var \Magento\SalesRule\Model\RuleFactory RuleFactory
     */
    protected $saleRuleFactory;

    /**
     * CatalogRule factory.
     *
     * @var \Magento\CatalogRule\Model\RuleFactory RuleFactory
     */
    protected $catalogRuleFactory;

    /**
     * StoreManagerInterface.
     *
     * @var \Magento\Store\Model\StoreManagerInterface StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Helper.
     *
     * @var \Riki\Rule\Helper\Data Data
     */
    protected $ruleHelper;

    /**
     * @var OrderColor
     */
    protected $orderColorResource;

    /**
     * @var promoItemHelper
     */
    protected $promoItemHelper;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var CumulatedGift
     */
    protected $cumulativeGift;

    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $adminQuoteSession;

    /**
     * @var \Riki\Sales\Helper\Data
     */
    protected $salesHelper;

    /**
     * @var array
     */
    protected $attributesForSave = [];

    /**
     * @var array
     */
    protected $attributesForSaveOrderItem = [];

    /**
     * @var array
     */
    protected $catalogRulesByKey = [];

    /**
     * OrderAfterPlaceObserver constructor.
     * @param \Riki\Rule\Model\OrderSapBookingFactory $orderSapBookingFactory
     * @param \Magento\SalesRule\Model\RuleFactory $saleRuleFactory
     * @param \Magento\CatalogRule\Model\RuleFactory $catalogRuleFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Riki\Rule\Helper\Data $ruleHelper
     * @param OrderColor $orderColorResource
     * @param promoItemHelper $promoItemHelper
     * @param Registry $registry
     * @param CumulatedGift $cumulatedGift
     * @param \Magento\Backend\Model\Session\Quote $adminQuoteSession
     * @param \Riki\Sales\Helper\Data $salesHelper
     */
    public function __construct(
        \Riki\Rule\Model\OrderSapBookingFactory $orderSapBookingFactory,
        \Magento\SalesRule\Model\RuleFactory $saleRuleFactory,
        \Magento\CatalogRule\Model\RuleFactory $catalogRuleFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Riki\Rule\Helper\Data $ruleHelper,
        OrderColor $orderColorResource,
        promoItemHelper $promoItemHelper,
        Registry $registry,
        CumulatedGift $cumulatedGift,
        \Magento\Backend\Model\Session\Quote $adminQuoteSession,
        \Riki\Sales\Helper\Data $salesHelper
    ) {
    
        $this->registry = $registry;
        $this->orderSapBookingFactory = $orderSapBookingFactory;
        $this->saleRuleFactory = $saleRuleFactory;
        $this->catalogRuleFactory = $catalogRuleFactory;
        $this->storeManager = $storeManager;
        $this->ruleHelper = $ruleHelper;
        $this->orderColorResource = $orderColorResource;
        $this->promoItemHelper = $promoItemHelper;
        $this->cumulativeGift = $cumulatedGift;
        $this->adminQuoteSession = $adminQuoteSession;
        $this->salesHelper = $salesHelper;
    }

    /**
     * Set persistent data into quote.
     *
     * @param \Magento\Framework\Event\Observer $observer Observer
     *
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var $order \Magento\Sales\Model\Order */
        $quote = $observer->getEvent()->getQuote();
        $order = $observer->getEvent()->getOrder();

        /**
         * Check Simulator order
         */
        if ($order instanceof \Riki\Subscription\Model\Emulator\Order) {
            return $this;
        }

        if (!empty($quote->getData('riki_course_id')) && !empty($quote->getData('riki_frequency_id')) && $order->getData('riki_type') == 'SPOT') {
            $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/debug.log');
            $logger = new \Zend\Log\Logger();
            $logger->addWriter($writer);

            $data = [
                'quote_id' => $quote->getId(),
                'riki_course_id' => $quote->getData('riki_course_id'),
                'riki_frequency_id' => $quote->getData('riki_frequency_id'),
                'order_id' => $order->getId(),
                'riki_type' => $order->getData('riki_type')
            ];
            $logger->info('NED-5413 subscription become sport order log:');
            $logger->info(print_r($data, true));
        }

        /**
         * When edit subscription order in admin, n_delivery must be get from previous order and set to quote
         */
        if ($this->adminQuoteSession->getOrderId()) {
            $orderBefore = $this->adminQuoteSession->getOrder();
            if ($orderBefore instanceof \Magento\Sales\Model\Order) {
                if ($orderBefore->getData('subscription_profile_id')) {
                    $quote->setData('n_delivery', $orderBefore->getData('subscription_order_time'));
                }
            }
        }

        $orderItems = $order->getAllItems();

        // cart price rule
        if ($order->getAppliedRuleIds()) {
            $ruleIds = explode(',', $order->getAppliedRuleIds());
            if ($ruleIds) {
                $rules = $this->saleRuleFactory->create()->getCollection()
                    ->addFieldToFilter('rule_id', ['in' => $ruleIds]);

                /** @var $rule \Magento\SalesRule\Model\Rule */
                foreach ($rules as $rule) {
                    /**
                     * If rule is free gift, save all WBS at item level for free gift only
                     */
                    if (\Zend_Validate::is($rule->getSimpleAction(), 'Regex', ['pattern' => '/ampromo/'])) {
                        foreach ($orderItems as $item) {
                            if ($this->promoItemHelper->getRuleIdByOrderItem($item) == $rule->getId()) {
                                $this->setWBSFromCartRuleForOrderItem($rule, $item, $order);
                            }
                        }
                    } else {
                        $appliedRuleItems = [];
                        foreach ($orderItems as $item) {
                            $ruleIds = explode(',', $item->getAppliedRuleIds());
                            if (in_array($rule->getId(), $ruleIds)) {
                                $appliedRuleItems[] = $item;
                            }
                        }

                        // have an item apply this rule -> apply WBS at order item level
                        if ($appliedRuleItems) {
                            foreach ($appliedRuleItems as $item) {
                                $this->setWBSFromCartRuleForOrderItem($rule, $item, $order);
                            }
                        } else {
                            $this->setWBSFromCartRuleForOrder($rule, $order);
                        }
                    }
                }
            }
        }

        // catalog rule
        $this->setCatalogRuleInfo($order, $quote);

        // cumulative gift
        if ($data = $this->registry->registry('cumulative_gift')) {
            $this->saveCumulativeData($order, $data);
        }

        // Save attribute for order
        if (!empty($this->attributesForSave)) {
            $order->getResource()->saveAttribute($order, $this->attributesForSave);
        }

        // Save attribute for order item
        if (!empty($this->attributesForSaveOrderItem)) {
            foreach ($orderItems as $item) {
                if (isset($this->attributesForSaveOrderItem[$item->getId()])) {
                    $item->getResource()->saveAttribute($item, $this->attributesForSaveOrderItem[$item->getId()]);
                }
            }
        }

        return $this;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Quote\Model\Quote $quote
     */
    protected function setCatalogRuleInfo(
        \Magento\Sales\Model\Order $order,
        \Magento\Quote\Model\Quote $quote
    ) {
        $customerGroupId = $order->getCustomerGroupId();
        $storeId = $order->getStoreId();
        $webId = $this->storeManager->getStore($storeId)->getWebsiteId();

        $courseId = $quote->getData('riki_course_id') ? $quote->getData('riki_course_id') : 0 ;
        $frequencyId = $quote->getData('riki_frequency_id') ? $quote->getData('riki_frequency_id') : 0 ;
        $nDelivery = $quote->getData('n_delivery') ?  $quote->getData('n_delivery') : 0;

        $orderItems = $order->getAllItems();

        $catalogRuleIdsByOrderItems = [];
        $allCatalogRuleIds = [];

        foreach ($orderItems as $item) {
            if ($this->salesHelper->isFreeAttachmentItem($item)) {
                continue;
            }

            $catalogRuleIdsByOrderItems[$item->getId()] = $this->getCatalogRulesByKey(
                $item->getProductId(),
                $customerGroupId,
                $webId,
                $courseId,
                $frequencyId,
                $nDelivery,
                date('Y-m-d', strtotime($order->getCreatedAtFormatted(\IntlDateFormatter::SHORT)))
            );

            if (!empty($catalogRuleIdsByOrderItems[$item->getId()])) {
                foreach ($catalogRuleIdsByOrderItems[$item->getId()] as $catalogRuleId) {
                    $allCatalogRuleIds[] = $catalogRuleId;
                }
            }
        }

        if (!empty($allCatalogRuleIds)) {
            /** @var \Magento\CatalogRule\Model\ResourceModel\Rule\Collection $catalogRuleCollection */
            $catalogRuleCollection = $this->catalogRuleFactory->create()->getCollection();
            $catalogRuleCollection->addFieldToFilter('rule_id', ['in' => $allCatalogRuleIds]);
            $catalogRules = $catalogRuleCollection->getItems();

            foreach ($orderItems as $item) {
                $itemId = $item->getId();

                if (isset($catalogRuleIdsByOrderItems[$itemId]) && !empty($catalogRuleIdsByOrderItems[$itemId])) {
                    $arrayId = [];
                    $dataRuleCatalog = [];

                    foreach ($catalogRuleIdsByOrderItems[$itemId] as $ruleId) {
                        if (isset($catalogRules[$ruleId])) {
                            $arrayId[] = $ruleId;
                            $this->setWBSFromCatalogRuleForOrderItem($catalogRules[$ruleId], $item);
                        }
                    }

                    // Save rule order item
                    if (!empty($arrayId) && $item->getRulePrice() !== null) {
                        $dataRuleCatalog['applied_rules_catalog'] = implode(',', $arrayId);
                        $this->orderColorResource->saveCatalogRuleForOrderItem($dataRuleCatalog, $item);
                    }
                }
            }
        }
    }

    /**
     * @param $productId
     * @param $customerGroupId
     * @param $webId
     * @param $courseId
     * @param $frequencyId
     * @param $nDelivery
     * @param $ruleDate
     * @return mixed
     */
    protected function getCatalogRulesByKey(
        $productId,
        $customerGroupId,
        $webId,
        $courseId,
        $frequencyId,
        $nDelivery,
        $ruleDate
    ) {
        $key = implode('_', func_get_args());

        if (!isset($this->catalogRulesByKey[$key])) {
            /** @var $catalogRuleModel \Riki\CatalogRule\Model\Rule */
            /** @var $catalogRuleResource \Riki\CatalogRule\Model\ResourceModel\Rule */
            $catalogRuleModel = $this->catalogRuleFactory->create();
            $catalogRuleResource = $catalogRuleModel->getResource();
            $this->catalogRulesByKey[$key] = $catalogRuleResource->getRulesApplied(
                $productId,
                $customerGroupId,
                $webId,
                $courseId,
                $frequencyId,
                $nDelivery,
                $ruleDate
            );
        }

        return $this->catalogRulesByKey[$key];
    }

    /**
     * Set WBS to Order & Order Item
     *
     * @param $rule
     * @param $item
     * @param $order
     */
    public function setWBSFromCartRuleForOrderItem($rule, $item, $order)
    {
        if ($value = $rule->getData('wbs_free_payment_fee')) {
            $order->setData('free_payment_wbs', $value);

            if (!in_array('free_payment_wbs', $this->attributesForSave)) {
                $this->attributesForSave[] = 'free_payment_wbs';
            }
        }
        if ($value = $rule->getData('wbs_free_delivery')) {
            $order->setData('free_delivery_wbs', $value);

            if (!in_array('free_delivery_wbs', $this->attributesForSave)) {
                $this->attributesForSave[] = 'free_delivery_wbs';
            }
        }
        if ($value = $rule->getData('wbs_promo_item_free_gift')) {
            $item->setData('foc_wbs', $value);
            $this->attributesForSaveOrderItem[$item->getId()][] = 'foc_wbs';
        }
        if ($value = $rule->getData('account_code')) {
            $item->setData('account_code', $value);
            $this->attributesForSaveOrderItem[$item->getId()][] = 'account_code';
        }
        if ($value = $rule->getData('sap_condition_type')) {
            $item->setData('sap_condition_type', $value);
            $this->attributesForSaveOrderItem[$item->getId()][] = 'sap_condition_type';
        }
    }

    /**
     * @param $rule
     * @param $order
     */
    public function setWBSFromCartRuleForOrder($rule, $order)
    {
        if ($value = $rule->getData('wbs_free_payment_fee')) {
            $order->setData('free_payment_wbs', $value);

            if (!in_array('free_payment_wbs', $this->attributesForSave)) {
                $this->attributesForSave[] = 'free_payment_wbs';
            }
        }
        if ($value = $rule->getData('wbs_free_delivery')) {
            $order->setData('free_delivery_wbs', $value);

            if (!in_array('free_delivery_wbs', $this->attributesForSave)) {
                $this->attributesForSave[] = 'free_delivery_wbs';
            }
        }
        if ($value = $rule->getData('account_code')) {
            $order->setData('account_code', $value);

            if (!in_array('account_code', $this->attributesForSave)) {
                $this->attributesForSave[] = 'account_code';
            }
        }
        if ($value = $rule->getData('sap_condition_type')) {
            $order->setData('sap_condition_type', $value);

            if (!in_array('sap_condition_type', $this->attributesForSave)) {
                $this->attributesForSave[] = 'sap_condition_type';
            }
        }
    }

    /**
     * @param $rule
     * @param $item
     */
    public function setWBSFromCatalogRuleForOrderItem($rule, $item)
    {
        if ($value = $rule->getData('machine_wbs')) {
            $item->setData('foc_wbs', $value);
            $this->attributesForSaveOrderItem[$item->getId()][] = 'foc_wbs';
        }
        if ($value = $rule->getData('sap_condition_type')) {
            $item->setData('sap_condition_type', $value);
            $this->attributesForSaveOrderItem[$item->getId()][] = 'sap_condition_type';
        }
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param array $data
     */
    public function saveCumulativeData($order, $data)
    {
        $result = [];

        for ($i = 0; $i < $data['qty_success']; $i++) {
            $result[] = [
                'consumer_db_id' => $data['consumer_db_id'],
                'sku' => $data['sku'],
                'wbs' => $data['wbs'],
                'order_number' => $order->getIncrementId(),
                'status' => 'Attached'
            ];
        }

        for ($i = 0; $i < $data['qty_missing']; $i++) {
            $result[] = [
                'consumer_db_id' => $data['consumer_db_id'],
                'sku' => $data['sku'],
                'wbs' => $data['wbs'],
                'order_number' => '',
                'status' => 'Not attached'
            ];
        }

        if (isset($data['update_ids'])) {
            $this->cumulativeGift->getResource()->updateGiftStatus($data['update_ids'], $order->getIncrementId(), 'Attached');
        }

        if ($result) {
            $this->cumulativeGift->getResource()->multiplyBunchInsert($result);
        }

        if (isset($data['sku'])) {
            foreach ($order->getAllItems() as $item) {
                if ($item->getSku() == $data['sku']) {
                    // set WBS
                    if ($data['wbs']) {
                        $item->setData('foc_wbs', $data['wbs']);
                        $this->attributesForSaveOrderItem[$item->getId()][] = 'foc_wbs';

                        /*set free of charge at item for Cumulative free gift*/
                        $item->setData('free_of_charge', 1);
                        $this->attributesForSaveOrderItem[$item->getId()][] = 'free_of_charge';
                    }
                    // set show on FO
                    $item->setData('visible_user_account', true);
                    $this->attributesForSaveOrderItem[$item->getId()][] = 'visible_user_account';
                }
            }
        }

        if (isset($data['new_counter']) && $data['new_counter']) {
            $this->cumulativeGift->setCustomerAPICounter($data['consumer_db_id'], $data['new_counter']);
        }
    }
}
