<?php

namespace Riki\CatalogRule\Model\Indexer;

use Magento\CatalogRule\Model\Indexer\IndexerTableSwapperInterface as TableSwapper;
use Magento\Catalog\Model\ResourceModel\Indexer\ActiveTableSwitcher;
use Magento\Framework\App\ObjectManager;
use Riki\SubscriptionCourse\Model\Course\Type as SubscriptionType;

/**
 * Reindex rule relations with products.
 */
class ReindexRuleProduct extends \Magento\CatalogRule\Model\Indexer\ReindexRuleProduct
{
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resource;

    /**
     * @var TableSwapper
     */
    private $tableSwapper;

    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $_subscriptionCourseFactory;
    /**
     * @var \Magento\Framework\App\State
     */
    protected $_appState;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    protected $_loadedData = [];

    /**
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param ActiveTableSwitcher $activeTableSwitcher
     * @param TableSwapper|null $tableSwapper
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        ActiveTableSwitcher $activeTableSwitcher,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Magento\Framework\App\State $state,
        \Psr\Log\LoggerInterface $logger,
        TableSwapper $tableSwapper = null
    ) {
        $this->resource = $resource;
        $this->tableSwapper = $tableSwapper ?: ObjectManager::getInstance()->get(TableSwapper::class);
        $this->_subscriptionCourseFactory = $courseFactory;
        $this->_appState = $state;
        $this->logger = $logger;

        parent::__construct(
            $resource,
            $activeTableSwitcher,
            $tableSwapper
        );
    }

    /**
     * Reindex information about rule relations with products.
     *
     * @param \Magento\CatalogRule\Model\Rule $rule
     * @param int $batchCount
     * @param bool $useAdditionalTable
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute(
        \Magento\CatalogRule\Model\Rule $rule,
        $batchCount,
        $useAdditionalTable = false
    ) {
        if (!$rule->getIsActive() || empty($rule->getWebsiteIds())) {
            return false;
        }

        $connection = $this->resource->getConnection();
        $websiteIds = $rule->getWebsiteIds();
        if (!is_array($websiteIds)) {
            $websiteIds = explode(',', $websiteIds);
        }

        \Magento\Framework\Profiler::start('__MATCH_PRODUCTS__');
        $productIds = $rule->getMatchingProductIds();
        \Magento\Framework\Profiler::stop('__MATCH_PRODUCTS__');

        $indexTable = $this->resource->getTableName('catalogrule_product');
        if ($useAdditionalTable) {
            $indexTable = $this->resource->getTableName(
                $this->tableSwapper->getWorkingTableName('catalogrule_product')
            );
        }

        $ruleId = $rule->getId();
        $customerGroupIds = $rule->getCustomerGroupIds();
        $fromTime = strtotime($rule->getFromTime());
        $toTime = strtotime($rule->getToTime());
        $sortOrder = (int)$rule->getSortOrder();
        $actionOperator = $rule->getSimpleAction();
        $actionAmount = $rule->getDiscountAmount();
        $actionStop = $rule->getStopRulesProcessing();

        $rule->getResource()
            ->loadSubscriptionCourseIdsAndFrequencyIds($rule);
        $subscriptionCourse = $rule->getApplySubscriptionCourseAndFrequency();

        $rows = [];

        foreach ($productIds as $productId => $validationByWebsite) {

            if (!$this->validateForSubscription($rule, $productId)) {
                continue;
            }

            foreach ($websiteIds as $websiteId) {
                if (empty($validationByWebsite[$websiteId])) {
                    continue;
                }
                foreach ($customerGroupIds as $customerGroupId) {

                    if (in_array($rule->getData('subscription'), [
                        \Riki\CatalogRule\Model\Rule::APPLY_SPOT_ONLY,
                        \Riki\CatalogRule\Model\Rule::APPLY_SPOT_SUBSCRIPTION
                    ])) {
                        $rows[] = [
                            'rule_id' => $ruleId,
                            'from_time' => $fromTime,
                            'to_time' => $toTime,
                            'website_id' => $websiteId,
                            'customer_group_id' => $customerGroupId,
                            'product_id' => $productId,
                            'action_operator' => $actionOperator,
                            'action_amount' => $actionAmount,
                            'action_stop' => $actionStop,
                            'sort_order' => $sortOrder,
                            'course_id' => 0,
                            'frequency_id' => 0
                        ];
                        if (count($rows) == $batchCount) {
                            $connection->insertOnDuplicate($indexTable, $rows);
                            $rows = [];
                        }
                    }

                    if (in_array($rule->getData('subscription'), [
                        \Riki\CatalogRule\Model\Rule::APPLY_SUBSCRIPTION_ONLY,
                        \Riki\CatalogRule\Model\Rule::APPLY_SPOT_SUBSCRIPTION
                    ])) {
                        foreach ($subscriptionCourse as $courseId => $subscriptionFrequency) {
                            foreach ($subscriptionFrequency as $frequencyId) {
                                $rows[] = [
                                    'rule_id' => $ruleId,
                                    'from_time' => $fromTime,
                                    'to_time' => $toTime,
                                    'website_id' => $websiteId,
                                    'customer_group_id' => $customerGroupId,
                                    'product_id' => $productId,
                                    'action_operator' => $actionOperator,
                                    'action_amount' => $actionAmount,
                                    'action_stop' => $actionStop,
                                    'sort_order' => $sortOrder,
                                    'course_id' => $courseId,
                                    'frequency_id' => $frequencyId
                                ];

                                if (count($rows) == $batchCount) {
                                    $connection->insertOnDuplicate($indexTable, $rows);
                                    $rows = [];
                                }
                            }
                        }
                    }
                }
            }
        }
        if (!empty($rows)) {
            $connection->insertOnDuplicate($indexTable, $rows);
        }
        return true;
    }

    /**
     * Validate for only subscription product
     *
     * @param $rule
     * @param $productId
     * @return bool
     */
    public function validateForSubscription($rule, $productId)
    {
        if ($rule->getData('subscription') == \Riki\CatalogRule\Model\Rule::APPLY_SPOT_ONLY
            || $rule->getData('subscription') == \Riki\CatalogRule\Model\Rule::APPLY_SPOT_SUBSCRIPTION
            || $rule->getData('is_machine')
        ) {
            return true;
        }

        if ($productId instanceof \Magento\Catalog\Model\Product) {
            $productId = $productId->getId();
        }

        return in_array($productId, $this->getProductIdsByRule($rule));
    }

    /**
     * Get all product ids by rule
     *
     * @param \Magento\CatalogRule\Model\Rule $rule
     * @return mixed
     */
    public function getProductIdsByRule(\Magento\CatalogRule\Model\Rule $rule)
    {
        $ruleId = $rule->getId();
        if (isset($this->_loadedData['product']['rule'][$ruleId])) {
            return $this->_loadedData['product']['rule'][$ruleId];
        }

        $productIds = [];
        foreach ($rule->getApplySubscription() as $courseId) {
            $productIds = array_unique(array_merge($productIds, $this->getProductIdsByCourseId($courseId)));
        }
        $this->_loadedData['product']['rule'][$ruleId] = $productIds;

        return $this->_loadedData['product']['rule'][$ruleId];
    }

    /**
     * Get product ids by course id
     *
     * @param $courseId
     * @return array
     */
    public function getProductIdsByCourseId($courseId)
    {
        if (isset($this->_loadedData['product']['course'][$courseId])) {
            return $this->_loadedData['product']['course'][$courseId];
        }

        /** @var \Riki\SubscriptionCourse\Model\ResourceModel\Course $course */
        $course = $this->_subscriptionCourseFactory->create()->getResource();
        $courseObj = $this->_subscriptionCourseFactory->create()->load($courseId);
        try {
            /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $products */
            if ($courseObj->getData('hanpukai_type') != SubscriptionType::TYPE_HANPUKAI_SEQUENCE) {
                $products = $this->_appState->emulateAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML, [$course, 'getAllProductByCourse'], [$courseId, 0]); // use area adminhtml to get all product @see getAllProductByCourse
            } else {
                $products = $this->_appState->emulateAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML, [$course, 'getAllProductHanpukaiSequenceConfig'], [$courseId, 0]);

            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $products = [];
        }
        $this->_loadedData['product']['course'][$courseId] = $products ? $products->getAllIds() : [];

        //load more product from additional category for indexer
        $aAdditionalCategories = $course->getAdditionalCategoryIds($courseId);
        if(!empty($aAdditionalCategories)){
            try {
                $productAdditional = $this->_appState->emulateAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML, [$course, 'getAllProductByCourse'], [$courseId, 0,null,true]);
                $productAdditionalIds = $productAdditional?$productAdditional->getAllIds():[];
                $this->_loadedData['product']['course'][$courseId] = array_merge( $this->_loadedData['product']['course'][$courseId],$productAdditionalIds);
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }

        foreach ($this->_loadedData['product']['course'][$courseId] as $id) {
            $this->_loadedData['course']['product'][$id] = $courseId;
        }


        return $this->_loadedData['product']['course'][$courseId];
    }
}
