<?php

namespace Riki\CatalogRule\Model\ResourceModel;

use Riki\CatalogRule\Model\Rule\SubscriptionDeliveryOptionsProvider;

class Rule extends \Magento\CatalogRule\Model\ResourceModel\Rule
{
    const PRELOAD_PRODUCT_IDS_KEY = 'catalog_rule_preload_product_ids';

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_timeZone;
    /**
     * @var array
     */
    protected $_subscriptionCourseIds;
    /**
     * @var array
     */
    protected $_subscriptionFrequencyIds;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;
    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $_quoteFactory;
    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $_quoteSession;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    protected $functionCache;

    /**
     * Store associated with rule entities information map
     *
     * @var array
     */
    protected $_associatedEntitiesMap = [
        'website'                => [
            'associations_table' => 'catalogrule_website',
            'rule_id_field'      => 'rule_id',
            'entity_id_field'    => 'website_id',
        ],
        'customer_group'         => [
            'associations_table' => 'catalogrule_customer_group',
            'rule_id_field'      => 'rule_id',
            'entity_id_field'    => 'customer_group_id',
        ],
        'subscription_course'    => [
            'associations_table' => 'catalogrule_subscription_course',
            'rule_id_field'      => 'rule_id',
            'entity_id_field'    => 'course_id',
            'frequency_id_field' => 'frequency_id'
        ],
        'subscription_frequency' => [
            'associations_table' => 'catalogrule_subscription_frequency',
            'rule_id_field'      => 'rule_id',
            'entity_id_field'    => 'frequency_id'
        ]
    ];

    /**
     * Rule constructor.
     *
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Backend\Model\Session\Quote\Proxy $quoteSession
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Magento\Checkout\Model\Session\Proxy $checkoutSession
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\Product\ConditionFactory $conditionFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $coreDate
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\CatalogRule\Helper\Data $catalogRuleData
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZone
     * @param null $connectionName
     */
    public function __construct(
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Backend\Model\Session\Quote\Proxy $quoteSession,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Checkout\Model\Session\Proxy $checkoutSession,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Product\ConditionFactory $conditionFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $coreDate,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\CatalogRule\Helper\Data $catalogRuleData,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZone,
        $connectionName = null
    )
    {
        $this->functionCache = $functionCache;
        $this->_request = $request;
        $this->_quoteSession = $quoteSession;
        $this->_quoteFactory = $quoteFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->_registry = $registry;
        $this->_timeZone = $timeZone;
        parent::__construct($context, $storeManager, $conditionFactory, $coreDate, $eavConfig,
            $eventManager, $catalogRuleData, $logger, $dateTime, $priceCurrency, $connectionName);
    }

    /**
     * Get Subscription Course Id
     *
     * @return int $courseId
     */
    public function getSubscriptionCourseId()
    {
        $courseId = 0;
        if ($currentCourseId = $this->_registry->registry('subscription-course-id')) {
            $courseId = $currentCourseId;
        } elseif ($currentCourseId = $this->_registry->registry(\Riki\Subscription\Model\Constant::RIKI_COURSE_ID)) {
            $courseId = $currentCourseId;
        } elseif ($quote = $this->getQuoteData()) {
            $courseId = $quote->getData('riki_course_id');
        }
        return $courseId;
    }

    /**
     * Get Subscription Frequency Id
     *
     * @return int $frequencyId
     */
    public function getSubscriptionFrequencyId()
    {
        $frequencyId = 0;
        if ($currentFrequencyId = $this->_registry->registry('subscription-frequency-id')) {
            $frequencyId = $currentFrequencyId;
        } elseif ($currentFrequencyId = $this->_registry->registry(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID)) {
            $frequencyId = $currentFrequencyId;
        } elseif ($quote = $this->getQuoteData()) {
            $frequencyId = $quote->getData('riki_frequency_id');
        }
        return $frequencyId;
    }

    /**
     * @return array
     */
    public function getSubscriptionProfileInfoFromRequest()
    {
        $nthDelivery = 1;
        $courseId = $this->getSubscriptionCourseId();
        $frequencyId = $this->getSubscriptionFrequencyId();

        if ($this->_quoteSession->getOrderId()) {
            $order = $this->_quoteSession->getOrder();
            if ($order instanceof \Magento\Sales\Model\Order) {
                if ($order->getData('subscription_profile_id')) {
                    $nthDelivery = $order->getData('subscription_order_time');
                }
            }
        }

        if ($this->_registry->registry('nth_delivery_override')) {
            $nthDelivery = $this->_registry->registry('nth_delivery_override');
        }

        $subscriptionProfile = $this->_registry->registry('subscription_profile_obj');
        $subscriptionProfileTmp = $this->_registry->registry('subscription_profile');
        if ($subscriptionProfile && $subscriptionProfileTmp) {
            if ($subscriptionProfileTmp->getData('frequency_unit')) {
                $subscriptionProfile->setData('frequency_unit', $subscriptionProfileTmp->getData('frequency_unit'));
            }
            if ($subscriptionProfileTmp->getData('frequency_interval')) {
                $subscriptionProfile->setData('frequency_interval', $subscriptionProfileTmp->getData('frequency_interval'));
            }
        }

        if ($subscriptionProfile) {
            if ((int)$subscriptionProfile->getData('create_order_flag')) {
                $nthDelivery = (int)$subscriptionProfile->getData('order_times');
            } else {
                $nthDelivery = (int)$subscriptionProfile->getData('order_times') + 1;
            }

            if ($subscriptionProfile->hasData('course_id')) {
                $courseId = $subscriptionProfile->getData('course_id');
            }
            if (($profileFrequencyId = $subscriptionProfile->getSubProfileFrequencyID())) {
                $frequencyId = $profileFrequencyId;
            }
        }

        if ($currentCourseId = $this->_request->getParam('course_id')) {
            $courseId = $currentCourseId;
        }
        if ($currentFrequencyId = $this->_request->getParam('frequency_id')) {
            $frequencyId = $currentFrequencyId;
        }

        return [
            $courseId,
            $frequencyId,
            $nthDelivery,
            $subscriptionProfile
        ];
    }

    /**
     * Retrieve product prices by catalog rule for specific date, website and customer group
     * Collect data with  product Id => price pairs
     *
     * @param \DateTime $date
     * @param int $websiteId
     * @param int $customerGroupId
     * @param array $productIds
     *
     * @return array
     */
    public function getRulePrices(\DateTimeInterface $date, $websiteId, $customerGroupId, $productIds)
    {
        list($courseId, $frequencyId, $nDelivery) = $this->getSubscriptionProfileInfoFromRequest();

        if (count($productIds) == 1) {
            $cacheKey = [$date->format('Ymd'), $websiteId, $customerGroupId, $courseId, $frequencyId, $nDelivery, end($productIds)];
            if ($this->functionCache->has($cacheKey)) {
                return $this->functionCache->load($cacheKey);
            }
        }

        $preloadIds = (array)$this->_registry->registry(self::PRELOAD_PRODUCT_IDS_KEY);
        if ($preloadIds) {
            array_push($productIds, ...$preloadIds);
            $productIds = array_unique($productIds);
            $this->_registry->unregister(self::PRELOAD_PRODUCT_IDS_KEY);
        }

        $_time = $this->_timeZone->date()->format('H:i:s');
        $connection = $this->getConnection();

        $subJoinCatalogProduct = $connection->select()
            ->from($this->getTable('catalogrule_product'))
            ->where('website_id = ?', $websiteId)
            ->where('customer_group_id = ?', $customerGroupId)
            ->where('course_id = ?', $courseId)
            ->where('frequency_id = ?', $frequencyId)
            ->where('product_id IN (?)', $productIds);

        $select = $connection->select()->from(
            ['rpp' => $this->getTable('catalogrule_product_price')],
            ['product_id', 'base_price', 'rule_price', 'rule_id']
        )->joinLeft(['rp' => new \Zend_Db_Expr('(' . $subJoinCatalogProduct . ')')],
            'rp.product_id=rpp.product_id AND rp.rule_id=rpp.rule_id AND rp.course_id=rpp.course_id AND rp.frequency_id = rpp.frequency_id AND rp.website_id = rpp.website_id AND rp.customer_group_id = rpp.customer_group_id ', ['action_amount', 'action_operator']
        )->joinLeft(['r' => 'catalogrule'],
            'rp.rule_id=r.rule_id', null
        )->where(
            'rpp.rule_date = ?',
            $date->format('Y-m-d')
        )->where(
            'rpp.website_id = ?',
            $websiteId
        )->where(
            'rpp.customer_group_id = ?',
            $customerGroupId
        )->where(
            'rpp.course_id = ?',
            $courseId
        )->where(
            'rpp.frequency_id = ?',
            $frequencyId
        )->where(
            'rpp.product_id IN(?)',
            $productIds
        )->where(
            'rpp.latest_start_date IS NULL OR CONCAT_WS(" ", rpp.rule_date, ?) >= CONCAT_WS(" ", rpp.latest_start_date, r.from_time)', $_time
        )->where(
            'rpp.earliest_end_date IS NULL OR CONCAT_WS(" ", rpp.rule_date, ?) <= CONCAT_WS(" ", rpp.earliest_end_date, r.to_time)', $_time
        );

        if ($nDelivery) {
            $sql = '(subscription_delivery = %d) 
                OR (subscription_delivery = %d AND ?%%delivery_n = 0) 
                OR (subscription_delivery = %d AND delivery_n = ?) 
                OR (subscription_delivery = %d AND delivery_n <= ?)';
            $sql = sprintf(
                $sql,
                SubscriptionDeliveryOptionsProvider::SUBSCRIPTION_DELIVERY_ALL,
                SubscriptionDeliveryOptionsProvider::SUBSCRIPTION_DELIVERY_EVERY_N,
                SubscriptionDeliveryOptionsProvider::SUBSCRIPTION_DELIVERY_ON_N,
                SubscriptionDeliveryOptionsProvider::SUBSCRIPTION_DELIVERY_FROM_N
            );

            $subSelect = $connection->select()
                ->from($this->getTable('catalogrule'), ['rule_id'])
                ->where($sql, $nDelivery);
            $select->where('rpp.rule_id IN (?)', $subSelect);
        }

        $productPriceData = $connection->fetchAll($select);

        $result = $this->calcRuleProductPrice($productPriceData);

        foreach ($productIds as $productId) {
            $cacheKey = [$date->format('Ymd'), $websiteId, $customerGroupId, $courseId, $frequencyId, $nDelivery, $productId];
            $this->functionCache->store(isset($result[$productId]) ? [$productId => $result[$productId]] : [$productId => []], $cacheKey);
        }

        return $result;
    }

    /**
     * @param array $productPriceData
     *
     * @return array
     */
    protected function calcRuleProductPrice($productPriceData = [])
    {
        if (!$productPriceData) {
            return [];
        }

        $productPriceRuleGroupData = [];
        foreach ($productPriceData as $productPrice) {
            $productPriceRuleGroupData[$productPrice['product_id'] . '_' . $productPrice['rule_id']] = $productPrice;
        }

        $productFinalPrice = [];
        foreach ($productPriceRuleGroupData as $productPrice) {

            if (!isset($productFinalPrice[$productPrice['product_id']])) {
                $productFinalPrice[$productPrice['product_id']] = $productPrice['base_price'];
            }

            $currentPrice = $productFinalPrice[$productPrice['product_id']];

            $currentPrice = $this->_catalogRuleData->calcPriceRule($productPrice['action_operator'], $productPrice['action_amount'], $currentPrice);

            $productFinalPrice[$productPrice['product_id']] = $currentPrice;
        }

        foreach ($productFinalPrice as $productId => $currentPrice) {

            if ($this->priceCurrency->getCurrency()->getCode() == 'JPY') {
                $currentPrice = round($currentPrice, 0);
            } else {
                $currentPrice = $this->priceCurrency->round($currentPrice);
            }

            $productFinalPrice[$productId] = $currentPrice;

            if (!$currentPrice) {
                $productFinalPrice[$productId] = number_format($currentPrice, 4, '.', '');
            }
        }
        return $productFinalPrice;
    }

    /**
     * @inheritdoc
     */
    public function _beforeSave(\Magento\Framework\Model\AbstractModel $object)
    {
        if (!$object->getSubscription()) {
            $object->setSubscriptionDelivery(SubscriptionDeliveryOptionsProvider::SUBSCRIPTION_DELIVERY_ALL);
        }
        if ($object->getSubscriptionDelivery() == SubscriptionDeliveryOptionsProvider::SUBSCRIPTION_DELIVERY_ALL) {
            $object->setDeliveryN(new \Zend_Db_Expr('NULL'));
        }
        return parent::_beforeSave($object);
    }

    /**
     * @inheritdoc
     */
    protected function _afterSave(\Magento\Framework\Model\AbstractModel $object)
    {
        if ($object->hasApplySubscriptionCourseAndFrequency()) {
            $applyData = $object->getApplySubscriptionCourseAndFrequency();

            if (!empty($applyData)) {
                $this->bindRuleToCourse($object->getId(), $applyData, 'subscription_course');
            }
        }

        return parent::_afterSave($object);
    }

    /**
     * @inheritdoc
     */
    protected function _afterLoad(\Magento\Framework\Model\AbstractModel $object)
    {
        $this->loadSubscriptionCourseIdsAndFrequencyIds($object);

        return parent::_afterLoad($object);
    }

    /**
     * Load apply_subscription from associations table
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     */
    public function loadSubscriptionCourseIds(\Magento\Framework\Model\AbstractModel $object)
    {
        if (!isset($this->_subscriptionCourseIds[$object->getId()])) {
            $this->_subscriptionCourseIds[$object->getId()] = (array)$this->getAssociatedEntityIds($object->getId(), 'subscription_course');
        }

        $object->setData('apply_subscription', $this->_subscriptionCourseIds[$object->getId()]);

        return $this;
    }

    /**
     * Load apply_frequency from associations table
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     */
    public function loadSubscriptionFrequencyIds(\Magento\Framework\Model\AbstractModel $object)
    {
        if (!isset($this->_subscriptionFrequencyIds[$object->getId()])) {
            $this->_subscriptionFrequencyIds[$object->getId()] = (array)$this->getAssociatedEntityIds($object->getId(), 'subscription_frequency');
        }
        $object->setData('apply_frequency', $this->_subscriptionFrequencyIds[$object->getId()]);

        return $this;
    }

    /**
     * Load apply_subscription from associations table
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     */
    public function loadSubscriptionCourseIdsAndFrequencyIds(\Magento\Framework\Model\AbstractModel $object)
    {
        if (!isset($this->_subscriptionCourseIds[$object->getId()])) {
            $applySubscription = $applyFrequency = $applySubscriptionCourseAndFrequency = [];
            $datas = $this->getAssociatedEntityIdsAndFrequencyIds($object->getId(), 'subscription_course');

            foreach ($datas as $data) {
                if (!in_array($data['course_id'], $applySubscription)) {
                    $applySubscription[] = $data['course_id'];
                }

                if (!in_array($data['frequency_id'], $applyFrequency)) {
                    $applyFrequency[] = $data['frequency_id'];
                }

                $applySubscriptionCourseAndFrequency[$data['course_id']][] = $data['frequency_id'];
            }

            $this->_subscriptionCourseIds[$object->getId()]['apply_subscription'] = $applySubscription;
            $this->_subscriptionCourseIds[$object->getId()]['apply_frequency'] = $applyFrequency;
            $this->_subscriptionCourseIds[$object->getId()]['apply_subscription_course_and_frequency'] = $applySubscriptionCourseAndFrequency;
        }

        $object->setData('apply_subscription', $this->_subscriptionCourseIds[$object->getId()]['apply_subscription']);
        $object->setData('apply_frequency', $this->_subscriptionCourseIds[$object->getId()]['apply_frequency']);
        $object->setData('apply_subscription_course_and_frequency', $this->_subscriptionCourseIds[$object->getId()]['apply_subscription_course_and_frequency']);

        return $this;
    }

    /**
     * Get current quote data
     *
     * @return bool
     */
    public function getQuoteData()
    {
        $quoteId = $this->_checkoutSession->getQuoteId();
        if (!$quoteId) {
            $quoteId = $this->_quoteSession->getQuoteId();
            if (!$quoteId) {
                return false;
            }
        }

        $quote = $this->_quoteFactory->create();
        $quoteResource = $quote->getResource();
        // get quote but no need to run collect total, avoid nest loop
        $quoteResource->loadByIdWithoutStore($quote, $quoteId);
        if (!$quote->getId()) {
            return false;
        }

        return $quote;
    }

    /**
     * @param $productId
     * @param $customerGroupId
     * @param $webId
     * @param $courseId
     * @param $frequencyId
     * @param $nDelivery
     * @param null $ruleDate
     * @return array
     */
    public function getRulesApplied($productId, $customerGroupId, $webId, $courseId, $frequencyId, $nDelivery, $ruleDate = null)
    {
        $connection = $this->getConnection();
        $select = $connection->select()->from(
            ['rpp' => $this->getTable('catalogrule_product_price')],
            ['rule_id']
        )->where(
            'product_id = ?', $productId
        )->where(
            'customer_group_id = ?', $customerGroupId
        )->where(
            'website_id = ?', $webId
        )->where(
            'course_id = ?', $courseId
        )->where(
            'frequency_id = ?', $frequencyId
        )->group('rule_id');

        if ($ruleDate) {
            $select->where(
                'rule_date = ?', $ruleDate
            );
        }

        if ($nDelivery) {
            $sql = '(subscription_delivery = %d) OR (subscription_delivery = %d AND ?%%delivery_n = 0) OR (subscription_delivery = %d AND delivery_n = ?) OR (subscription_delivery = %d AND delivery_n <= ?)';
            $sql = sprintf($sql, SubscriptionDeliveryOptionsProvider::SUBSCRIPTION_DELIVERY_ALL, SubscriptionDeliveryOptionsProvider::SUBSCRIPTION_DELIVERY_EVERY_N, SubscriptionDeliveryOptionsProvider::SUBSCRIPTION_DELIVERY_ON_N, SubscriptionDeliveryOptionsProvider::SUBSCRIPTION_DELIVERY_FROM_N);
            $subSelect = $connection->select()
                ->from($this->getTable('catalogrule'), ['rule_id'])
                ->where($sql, $nDelivery);
            $select->where('rpp.rule_id IN (?)', $subSelect);
        }

        return $connection->fetchCol($select);
    }

    public function getSubscriptionCourseIds($ruleId)
    {
        return $this->getAssociatedEntityIds($ruleId, 'subscription_course');
    }

    public function getSubscriptionFrequencyIds($ruleId)
    {
        return $this->getAssociatedEntityIds($ruleId, 'subscription_frequency');
    }

    public function getMachineCatalogRule($courseId, $productId)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from(['cr' => $this->getTable('catalogrule')], 'cr.rule_id')
            ->joinLeft(
                ['csc' => $this->getTable('catalogrule_subscription_course')],
                'csc.rule_id = cr.rule_id',
                []
            )
            ->where('cr.is_machine = ?', 1)
            ->where('csc.course_id = ?', $courseId)
            ->where('cr.machine_id = ?', $productId);

        return $connection->fetchOne($select);
    }

    public function removeRule($ruleIds)
    {
        $connection = $this->getConnection();
        $condRule = ['rule_id IN(?)' => $ruleIds, 'is_machine=?' => 1];
        $condRuleSub = ['rule_id IN(?)' => $ruleIds];
        $connection->delete($this->getTable('catalogrule'), $condRule);
        $connection->delete($this->getTable('catalogrule_product'), $condRuleSub);
        $connection->delete($this->getTable('catalogrule_customer_group'), $condRuleSub);
        $connection->delete($this->getTable('catalogrule_group_website'), $condRuleSub);
    }

    public function getCatalogRuleByIds($ids)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from(['rp' => $this->getTable('catalogrule_product')],
                ['rp.rule_product_id', 'rp.rule_id', 'rp.course_id', 'rp.frequency_id'])
            ->where('rp.rule_id IN (?)', $ids)
            ->group('rp.course_id', 'rp.frequency_id');

        return $connection->fetchAll($select);
    }

    /**
     * Bind specified rules to subscription course
     *
     * @param int[]|int|string $ruleIds
     * @param int[] $applyData
     * @param string $entityType
     * @return $this
     * @throws \Exception
     */
    public function bindRuleToCourse($ruleIds, $applyData, $entityType)
    {
        $this->getConnection()->beginTransaction();

        try {
            $this->_multiplyBunchInsertCourse($ruleIds, $applyData, $entityType);
        } catch (\Exception $e) {
            $this->getConnection()->rollback();
            throw $e;
        }

        $this->getConnection()->commit();

        return $this;
    }

    /**
     * Multiply rule ids by entity ids and insert
     *
     * @param int|[] $ruleIds
     * @param int|[] $applyData
     * @param string $entityType
     * @return $this
     */
    protected function _multiplyBunchInsertCourse($ruleIds, $applyData, $entityType)
    {
        if (empty($ruleIds) || empty($applyData)) {
            return $this;
        }
        if (!is_array($ruleIds)) {
            $ruleIds = [(int)$ruleIds];
        }
        $data = $deleteCourseIds = $deleteFrequencyIds = [];
        $count = 0;
        $entityInfo = $this->_getAssociatedEntityInfo($entityType);
        foreach ($ruleIds as $ruleId) {
            foreach ($applyData as $courseId => $frequencyIds) {
                $deleteCourseIds[] = $courseId;
                foreach ($frequencyIds as $frequencyId) {
                    $deleteFrequencyIds[] = $frequencyId;
                    $data[] = [
                        $entityInfo['rule_id_field']      => $ruleId,
                        $entityInfo['entity_id_field']    => $courseId,
                        $entityInfo['frequency_id_field'] => $frequencyId,
                    ];
                    $count++;
                    if ($count % 1000 == 0) {
                        $this->getConnection()->insertOnDuplicate(
                            $this->getTable($entityInfo['associations_table']),
                            $data,
                            [$entityInfo['rule_id_field']]
                        );
                        $data = [];
                    }
                }
            }
        }
        if (!empty($data)) {
            $this->getConnection()->insertOnDuplicate(
                $this->getTable($entityInfo['associations_table']),
                $data,
                [$entityInfo['rule_id_field']]
            );
        }

        $this->getConnection()->delete(
            $this->getTable($entityInfo['associations_table']),
            $this->getConnection()->quoteInto(
                $entityInfo['rule_id_field'] . ' IN (?) AND (',
                $ruleIds
            ) . $this->getConnection()->quoteInto(
                $entityInfo['entity_id_field'] . ' NOT IN (?) OR (',
                $deleteCourseIds
            ) . $this->getConnection()->quoteInto(
                $entityInfo['entity_id_field'] . ' IN (?) AND ',
                $deleteCourseIds
            ) . $this->getConnection()->quoteInto(
                $entityInfo['frequency_id_field'] . ' NOT IN (?) ))',
                $deleteFrequencyIds
            )
        );
        return $this;
    }

    /**
     * Retrieve rule's associated entity Ids by entity type
     *
     * @param int $ruleId
     * @param string $entityType
     * @return array
     */
    public function getAssociatedEntityIdsAndFrequencyIds($ruleId, $entityType)
    {
        $entityInfo = $this->_getAssociatedEntityInfo($entityType);

        $select = $this->getConnection()->select()->from(
            $this->getTable($entityInfo['associations_table']),
            [$entityInfo['entity_id_field'], $entityInfo['frequency_id_field']]
        )->where(
            $entityInfo['rule_id_field'] . ' = ?',
            $ruleId
        );

        return $this->getConnection()->fetchAll($select);
    }
}
