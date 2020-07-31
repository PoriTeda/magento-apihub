<?php
namespace Riki\CatalogRule\Model\Indexer;

use Magento\Framework\App\ObjectManager;
use Magento\Catalog\Model\Product;
use Magento\CatalogRule\Model\Rule;
use Riki\SubscriptionCourse\Model\Course\Type as SubscriptionType;
use Magento\CatalogRule\Model\Indexer\IndexerTableSwapperInterface as TableSwapper;
use Magento\CatalogRule\Model\Indexer\IndexBuilder\ProductLoader;

class IndexBuilder extends \Magento\CatalogRule\Model\Indexer\IndexBuilder
{
    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $_subscriptionCourseFactory;
    /**
     * @var \Magento\Framework\App\State
     */
    protected $_appState;
    /**
     * @var array
     */
    protected $_loadedData;

    /** @var ReindexRuleProduct|null  */
    private $_reindexRuleProduct;

    /** @var \Magento\CatalogRule\Model\Indexer\ReindexRuleGroupWebsite|null  */
    private $_reindexRuleGroupWebsite;

    /** @var ReindexRuleProductPrice|null  */
    private $_reindexRuleProductPrice;

    /** @var ProductPriceCalculator|null  */
    private $_productPriceCalculator;

    /** @var TableSwapper|null  */
    private $_tableSwapper;

    /**
     * @var ProductLoader
     */
    private $productLoader;

    /**
     * @var \Riki\Subscription\Model\Profile\ResourceModel\Indexer\Profile
     */
    protected $reindexProfile;

    /**
     * IndexBuilder constructor.
     * @param \Magento\Framework\App\State $appState
     * @param \Riki\SubscriptionCourse\Model\CourseFactory $subscriptionCourseFactory
     * @param \Magento\CatalogRule\Model\ResourceModel\Rule\CollectionFactory $ruleCollectionFactory
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Framework\Stdlib\DateTime $dateFormat
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Riki\Subscription\Model\Profile\ResourceModel\Indexer\Profile $reindexProfile
     * @param ProductPriceCalculator|null $productPriceCalculator
     * @param ReindexRuleProduct|null $reindexRuleProduct
     * @param \Magento\CatalogRule\Model\Indexer\ReindexRuleGroupWebsite|null $reindexRuleGroupWebsite
     * @param \Magento\CatalogRule\Model\Indexer\RuleProductsSelectBuilder|null $ruleProductsSelectBuilder
     * @param ReindexRuleProductPrice|null $reindexRuleProductPrice
     * @param \Magento\CatalogRule\Model\Indexer\RuleProductPricesPersistor|null $pricesPersistor
     * @param \Magento\Catalog\Model\ResourceModel\Indexer\ActiveTableSwitcher|null $activeTableSwitcher
     * @param ProductLoader|null $productLoader
     * @param TableSwapper|null $tableSwapper
     * @param int $batchCount
     */
    public function __construct(
        \Magento\Framework\App\State $appState,
        \Riki\SubscriptionCourse\Model\CourseFactory $subscriptionCourseFactory,
        \Magento\CatalogRule\Model\ResourceModel\Rule\CollectionFactory $ruleCollectionFactory,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\Stdlib\DateTime $dateFormat,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Riki\Subscription\Model\Profile\ResourceModel\Indexer\Profile $reindexProfile,
        \Riki\CatalogRule\Model\Indexer\ProductPriceCalculator $productPriceCalculator = null,
        \Riki\CatalogRule\Model\Indexer\ReindexRuleProduct $reindexRuleProduct = null,
        \Magento\CatalogRule\Model\Indexer\ReindexRuleGroupWebsite $reindexRuleGroupWebsite = null,
        \Magento\CatalogRule\Model\Indexer\RuleProductsSelectBuilder $ruleProductsSelectBuilder = null,
        \Riki\CatalogRule\Model\Indexer\ReindexRuleProductPrice $reindexRuleProductPrice = null,
        \Magento\CatalogRule\Model\Indexer\RuleProductPricesPersistor $pricesPersistor = null,
        \Magento\Catalog\Model\ResourceModel\Indexer\ActiveTableSwitcher $activeTableSwitcher = null,
        ProductLoader $productLoader = null,
        TableSwapper $tableSwapper = null,
        $batchCount = 1000
    )
    {
        $this->_appState = $appState;
        $this->_subscriptionCourseFactory = $subscriptionCourseFactory;

        $this->_reindexRuleProduct = ObjectManager::getInstance()->get(
            \Riki\CatalogRule\Model\Indexer\ReindexRuleProduct::class
        );
        $this->_reindexRuleGroupWebsite = $reindexRuleGroupWebsite ?: ObjectManager::getInstance()->get(
            \Magento\CatalogRule\Model\Indexer\ReindexRuleGroupWebsite::class
        );
        $this->_reindexRuleProductPrice = ObjectManager::getInstance()->get(
            \Riki\CatalogRule\Model\Indexer\ReindexRuleProductPrice::class
        );
        $this->_productPriceCalculator = $productPriceCalculator ?: ObjectManager::getInstance()->get(
            \Riki\CatalogRule\Model\Indexer\ProductPriceCalculator::class
        );
        $this->productLoader = $productLoader ?? ObjectManager::getInstance()->get(
            ProductLoader::class
        );
        $this->_tableSwapper = $tableSwapper ?: ObjectManager::getInstance()->get(
            \Magento\CatalogRule\Model\Indexer\IndexerTableSwapperInterface::class
        );

        $this->reindexProfile = $reindexProfile;
        parent::__construct(
            $ruleCollectionFactory,
            $priceCurrency,
            $resource,
            $storeManager,
            $logger,
            $eavConfig,
            $dateFormat,
            $dateTime,
            $productFactory,
            $batchCount,
            $productPriceCalculator,
            $reindexRuleProduct,
            $reindexRuleGroupWebsite,
            $ruleProductsSelectBuilder,
            $reindexRuleProductPrice,
            $pricesPersistor,
            $activeTableSwitcher,
            $productLoader,
            $tableSwapper
        );
    }

    /**
     * Full reindex Template method
     *
     * @return void
     */
    protected function doReindexFull()
    {
        foreach ($this->getAllRules() as $rule) {
            $this->_reindexRuleProduct->execute($rule, $this->batchCount, true);
        }

        $this->_reindexRuleProductPrice->execute($this->batchCount, null, true);
        $this->_reindexRuleGroupWebsite->execute(true);

        $this->_tableSwapper->swapIndexTables(
            [
                $this->getTable('catalogrule_product'),
                $this->getTable('catalogrule_product_price'),
                $this->getTable('catalogrule_group_website')
            ]
        );

        /** Remove row when is_invalid = 1 */
        $this->reindexProfile->clearCacheByFlag();
    }

    /**
     * @inheritdoc
     */
    protected function cleanByIds($productIds)
    {
        $query = $this->connection->deleteFromSelect(
            $this->connection
                ->select()
                ->from($this->resource->getTableName('catalogrule_product'), 'product_id')
                ->distinct()
                ->where('product_id IN (?)', $productIds),
            $this->resource->getTableName('catalogrule_product')
        );
        $this->connection->query($query);

        $query = $this->connection->deleteFromSelect(
            $this->connection->select()
                ->from($this->resource->getTableName('catalogrule_product_price'), 'product_id')
                ->distinct()
                ->where('product_id IN (?)', $productIds),
            $this->resource->getTableName('catalogrule_product_price')
        );
        $this->connection->query($query);
    }

    /**
     * @param \Magento\CatalogRule\Model\Rule $rule
     * @param \Magento\Catalog\Model\Product $product
     * @return $this
     * @throws \Exception
     */
    protected function applyRule(\Magento\CatalogRule\Model\Rule $rule, $product)
    {
        $ruleId = $rule->getId();
        $productId = $product->getId();
        $websiteIds = array_intersect($product->getWebsiteIds(), $rule->getWebsiteIds());

        if (!$rule->validate($product)) {
            return $this;
        }

        $this->connection->delete(
            $this->resource->getTableName('catalogrule_product'),
            [
                $this->connection->quoteInto('rule_id = ?', $ruleId),
                $this->connection->quoteInto('product_id = ?', $productId)
            ]
        );

        $customerGroupIds = $rule->getCustomerGroupIds();
        $fromTime = strtotime($rule->getFromTime());
        $toTime = strtotime($rule->getToTime());
        $sortOrder = (int)$rule->getSortOrder();
        $actionOperator = $rule->getSimpleAction();
        $actionAmount = $rule->getDiscountAmount();
        $actionStop = $rule->getStopRulesProcessing();

        $rule->getResource()
            ->loadSubscriptionCourseIds($rule)
            ->loadSubscriptionFrequencyIds($rule);
        $subscriptionCourses = $rule->getApplySubscription();
        $subscriptionFreqs = $rule->getApplyFrequency();

        $rows = [];
        try {
            foreach ($websiteIds as $websiteId) {
                foreach ($customerGroupIds as $customerGroupId) {

                    if (
                        in_array($rule->getData('subscription'), [
                            \Riki\CatalogRule\Model\Rule::APPLY_SPOT_ONLY,
                            \Riki\CatalogRule\Model\Rule::APPLY_SPOT_SUBSCRIPTION
                        ])
                    ) {
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

                        if (count($rows) == $this->batchCount) {
                            $this->connection->insertOnDuplicate($this->getTable('catalogrule_product'), $rows);
                            $rows = [];
                        }
                    }

                    if (
                    in_array($rule->getData('subscription'), [
                        \Riki\CatalogRule\Model\Rule::APPLY_SUBSCRIPTION_ONLY,
                        \Riki\CatalogRule\Model\Rule::APPLY_SPOT_SUBSCRIPTION
                    ])
                    ) {

                        foreach ($subscriptionCourses as $course) {
                            foreach ($subscriptionFreqs as $freq) {
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
                                    'course_id' => $course,
                                    'frequency_id' => $freq
                                ];

                                if (count($rows) == $this->batchCount) {
                                    $this->connection->insertOnDuplicate($this->getTable('catalogrule_product'), $rows);
                                    $rows = [];
                                }
                            }
                        }
                    }
                }
            }

            if (!empty($rows)) {
                $this->connection->insertOnDuplicate($this->resource->getTableName('catalogrule_product'), $rows);
            }
        } catch (\Exception $e) {
            throw $e;
        }

        $this->_reindexRuleProductPrice->execute($this->batchCount, $product);
        $this->_reindexRuleGroupWebsite->execute();

        return $this;
    }

    /**
     * @param Product|null $product
     * @throws \Exception
     * @return $this
     * @deprecated
     * @see \Magento\CatalogRule\Model\Indexer\ReindexRuleProductPrice::execute
     * @see \Magento\CatalogRule\Model\Indexer\ReindexRuleGroupWebsite::execute
     */
    protected function applyAllRules(Product $product = null)
    {
        $this->_reindexRuleProductPrice->execute($this->batchCount, $product);
        $this->_reindexRuleGroupWebsite->execute();
        return $this;
    }

    /**
     * @param Rule $rule
     * @return $this
     * @deprecated
     * @see \Magento\CatalogRule\Model\Indexer\ReindexRuleProduct::execute
     */
    protected function updateRuleProductData(Rule $rule)
    {
        $ruleId = $rule->getId();
        if ($rule->getProductsFilter()) {
            $this->connection->delete(
                $this->getTable('catalogrule_product'),
                ['rule_id=?' => $ruleId, 'product_id IN (?)' => $rule->getProductsFilter()]
            );
        } else {
            $this->connection->delete(
                $this->getTable('catalogrule_product'),
                $this->connection->quoteInto('rule_id=?', $ruleId)
            );
        }

        $this->_reindexRuleProduct->execute($rule, $this->batchCount);
        return $this;
    }

    /**
     * @param array $ruleData
     * @param null $productData
     * @return float
     * @deprecated
     * @see \Magento\CatalogRule\Model\Indexer\ProductPriceCalculator::calculate
     */
    protected function calcRuleProductPrice($ruleData, $productData = null)
    {
        return $this->_productPriceCalculator->calculate($ruleData, $productData);
    }

    /**
     * @param int $websiteId
     * @param int|null $productId
     * @return \Zend_Db_Statement_Interface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getRuleProductsStmt($websiteId, $productId = null)
    {
        /**
         * Sort order is important
         * It used for check stop price rule condition.
         * website_id   customer_group_id   product_id  sort_order
         *  1           1                   1           0
         *  1           1                   1           1
         *  1           1                   1           2
         * if row with sort order 1 will have stop flag we should exclude
         * all next rows for same product id from price calculation
         */
        $select = $this->connection->select()->from(
            ['rp' => $this->getTable('catalogrule_product')]
        )->order(
            ['rp.website_id', 'rp.customer_group_id', 'rp.product_id', 'rp.course_id', 'rp.sort_order', 'rp.rule_id']
        );

        if ($productId !== null) {
            $select->where('rp.product_id=?', $productId);
        }

        /**
         * Join default price and websites prices to result
         */
        $priceAttr = $this->eavConfig->getAttribute(\Magento\Catalog\Model\Product::ENTITY, 'price');
        $priceTable = $priceAttr->getBackend()->getTable();
        $attributeId = $priceAttr->getId();

        $joinCondition = '%1$s.entity_id=rp.product_id AND (%1$s.attribute_id='
            . $attributeId
            . ') and %1$s.store_id=%2$s';

        $select->join(
            ['pp_default' => $priceTable],
            sprintf($joinCondition, 'pp_default', \Magento\Store\Model\Store::DEFAULT_STORE_ID),
            []
        );

        $website = $this->storeManager->getWebsite($websiteId);
        $defaultGroup = $website->getDefaultGroup();
        if ($defaultGroup instanceof \Magento\Store\Model\Group) {
            $storeId = $defaultGroup->getDefaultStoreId();
        } else {
            $storeId = \Magento\Store\Model\Store::DEFAULT_STORE_ID;
        }

        $select->joinInner(
            ['product_website' => $this->getTable('catalog_product_website')],
            'product_website.product_id=rp.product_id '
            . 'AND product_website.website_id = rp.website_id '
            . 'AND product_website.website_id='
            . $websiteId,
            []
        );

        $tableAlias = 'pp' . $websiteId;
        $select->joinLeft(
            [$tableAlias => $priceTable],
            sprintf($joinCondition, $tableAlias, $storeId),
            []
        );
        $select->columns([
            'default_price' =>$this->connection->getIfNullSql($tableAlias . '.value', 'pp_default.value'),
        ]);

        return $this->connection->query($select);
    }
}
