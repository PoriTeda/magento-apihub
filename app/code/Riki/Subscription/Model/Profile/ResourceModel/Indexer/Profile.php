<?php
namespace Riki\Subscription\Model\Profile\ResourceModel\Indexer;

use Magento\Framework\Exception\LocalizedException;

class Profile extends \Magento\Indexer\Model\ResourceModel\AbstractResource
{
    protected $_tags = ['profile_simulate'];
    protected $_lifeTime = 2592000; // 1 month
    protected $_isRun = false;
    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    protected $_cache;

    protected $_searchCriteriaBuilder;

    protected $_profileRepository;

    protected $_simulator;

    protected $_sortOrderBuilder;

    protected $logger;

    protected $_rule;

    protected $_profileResource;

    /* @var \Riki\Subscription\Helper\Indexer\Data */
    protected $indexerHelper;

    protected $_salesRule;

    protected $customerRepository;

    /**
     * @var \Riki\Subscription\Api\GenerateOrder\ProfileBuilderInterface
     */
    protected $profileBuilder;

    /**
     * @var \Riki\Subscription\Model\Profile\Order\ProfileOrderFactory
     */
    protected $profileOrderFactory;

    /**
     * @var \Magento\Framework\MessageQueue\PublisherInterface
     */
    protected $publisher;

    public function __construct(
        \Riki\Subscription\Helper\Indexer\Data\Proxy $indexerHelper,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Framework\Indexer\Table\StrategyInterface $tableStrategy,
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Riki\Subscription\Model\Profile\ProfileRepository $profileRepository,
        \Riki\Subscription\Helper\Order\Simulator $simulator,
        \Magento\Framework\Api\SortOrderBuilder $sortOrderBuilder,
        \Psr\Log\LoggerInterface $logger,
        \Riki\CatalogRule\Model\ResourceModel\Rule $rule,
        \Riki\Subscription\Model\Profile\ResourceModel\Profile $profileResource,
        \Magento\SalesRule\Model\RuleFactory $ruleFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Riki\Subscription\Api\GenerateOrder\ProfileBuilderInterface $profileBuilderInterface,
        \Riki\Subscription\Model\Profile\Order\ProfileOrderFactory $profileOrderFactory,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher,
        $connectionName = null
    ) {
        $this->customerRepository = $customerRepository;
        $this->indexerHelper = $indexerHelper;
        $this->_salesRule = $ruleFactory;
        $this->_profileResource = $profileResource;
        $this->_rule = $rule;
        $this->_sortOrderBuilder = $sortOrderBuilder;
        $this->_simulator = $simulator;
        $this->_profileRepository = $profileRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_cache = $cache;
        $this->logger = $logger;
        $this->profileBuilder = $profileBuilderInterface;
        $this->profileOrderFactory = $profileOrderFactory;
        $this->publisher = $publisher;
        parent::__construct($context, $tableStrategy, $connectionName);
    }

    protected function _construct()
    {
        $this->_init('subscription_profile_simulate_cache', 'profile_simulate_id');
    }

    /**
     * @param null $ids
     * @return $this
     */
    public function reindexAll($ids = null)
    {
        $sortOrder = $this->_sortOrderBuilder->setField('profile_id')->setDirection('DESC')->create();
        $profileSearch = $this->_searchCriteriaBuilder
            ->addFilter('status', 1, 'eq')
            ->addSortOrder($sortOrder);
        if ($ids) {
            $profileSearch->addFilter('profile_id', $ids, 'in');
        }
        $existIds = $this->getProfileCacheIds();
        if ($existIds) {
            $profileSearch->addFilter('profile_id', $existIds, 'nin');
        }
        $profileSearch = $profileSearch->create();
        $profiles = $this->_profileRepository->getList($profileSearch);

        if ($profiles->getTotalCount()) {

            $profiles = $profiles->getItems();

            foreach ($profiles as $profile) {
                $profileId = $profile->getProfileId();

                /*publish profile to queue for processing later*/
                $profileReindex =  $this->profileOrderFactory->create();
                $profileReindex->setProfileId($profileId);
                $profileItemBuilder = $this->profileBuilder->setItems([$profileReindex]);
                try {
                    //$this->publisher->publish('profile.indexer', $profileItemBuilder);
                }catch (\Exception $e){
                    $this->logger->critical($e);
                }

            }
        }

        return $this;
    }

    /**
     * @param $profileId
     *
     * @return $this
     */
    public function reindexRow($profileId)
    {
        /*publish profile to queue for processing later*/
        $profileReindex =  $this->profileOrderFactory->create();
        $profileReindex->setProfileId($profileId);
        $profileItemBuilder = $this->profileBuilder->setItems([$profileReindex]);
        try {
            //$this->publisher->publish('profile.indexer', $profileItemBuilder);
        }catch (\Exception $e){
            $this->logger->critical($e);
        }
    }

    /**
     * @param null $ruleProductIds
     * @param bool $isRemove
     * @return $this
     */
    public function reindexCatalogruleAll($ruleProductIds = null, $isRemove = false)
    {
        $rules = $this->_rule->getCatalogRuleByIds($ruleProductIds);
        foreach ($rules as $rule) {
            if ($rule['course_id']) { // subscription rule
                $profileIds = $this->_profileResource->getProfileByCourse($rule['course_id'], $rule['frequency_id']);
                if ($profileIds) {
                    $isRemove ? $this->removeDataProfile($profileIds) : $this->reindexAll($profileIds);
                }
            }
        }

        return $this;
    }

    /**
     * Clear profile cache simulate after save catalogrule
     *
     * @param array $data
     */
    public function reindexProfileByCatalogrule($data)
    {
        $arrProfile = [];
        foreach ($data as $courseId => $frequenciesData) {
            foreach ($frequenciesData as $frequencyId) {
                $profileIds = $this->_profileResource->getProfileByCourse($courseId, $frequencyId);
                if ($profileIds) {
                    $arrProfile = array_merge($arrProfile, $profileIds);
                }
            }
        }

        if (!empty($arrProfile)) {
            $this->addFlagRemoveWhenReindex($arrProfile);

        }
    }

    /**
     * Remove row when is_invalid = 1
     */
    public function clearCacheByFlag()
    {
        $this->getConnection()->delete(
            $this->getTable('subscription_profile_simulate_cache'),
            ['is_invalid' => 1]
        );
    }

    /**
     * @param $ids
     */
    public function addFlagRemoveWhenReindex($ids)
    {
        $this->getConnection()->update(
            $this->getTable('subscription_profile_simulate_cache'),
            ['is_invalid' => 1],
            ['profile_id IN (?)' => $ids]
        );
    }

    /**
     * @param $ids
     */
    public function clearCacheByProfileId($ids)
    {
        $this->getConnection()->delete(
            $this->getTable('subscription_profile_simulate_cache'),
            ['profile_id IN (?)' => $ids]
        );
    }
    /**
     * @param $ruleId
     * @param $data
     */
    public function clearCacheByCatalogrule($ruleId, $data)
    {
        $this->reindexCatalogruleAll([$ruleId], true, $data);
    }

    /**
     * @param $ruleId
     * @param bool $isRemove
     * @return $this
     */
    public function reindexSalesruleAll($ruleId, $isRemove = false)
    {
        $rule = $this->_salesRule->create()->load($ruleId);
        if ($rule->getData('subscription')) { // subscription rule
            $courseIds = $rule->getData('apply_subscription');
            $frequencyIds = $rule->getData('apply_frequency');
            foreach ($courseIds as $courseId) {
                foreach ($frequencyIds as $frequencyId) {
                    $profileIds = $this->_profileResource->getProfileByCourse($courseId, $frequencyId);
                    if ($profileIds) {
                        $isRemove ? $this->removeDataProfile($profileIds) : $this->reindexAll($profileIds);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * @param $ruleId
     */
    public function clearCacheBySalesrule($ruleId)
    {
        $this->reindexSalesruleAll($ruleId, true);
    }

    /**
     * @param $data
     */
    public function saveToTableWhenSimulate($data)
    {
        $dataTable = [
            'profile_id' => $data['profile_id'],
            'customer_id' => $data['customer_id'],
            'data_serialized' => $data['data_serialized'],
            'delivery_number' => $data['delivery_number']
        ];
        $this->saveToTable($dataTable);
    }

    /**
     * @param $simulatorOrder
     * @return array|bool
     */
    protected function prepareData($simulatorOrder)
    {
        if ($simulatorOrder) {
            $data = [
                'discount' => $simulatorOrder->getDiscountAmount(),
                'shipping_fee' => $simulatorOrder->getShippingInclTax(),
                'payment_method_fee' => $simulatorOrder->getFee(),
                'wrapping_fee' => $simulatorOrder->getData('gw_items_base_price_incl_tax'),
                'total_amount' => $simulatorOrder->getGrandTotal()
            ];
            return $data;
        }
        return false;
    }

    /**
     * @param $key
     * @param $data
     */
    protected function createCache($key, $data)
    {
        $this->_cache->save($data, $key, $this->_tags, $this->_lifeTime);
        // easy to see
        $this->_isRun = $this->_isRun ?: true;
        //print ".";
    }

    /**
     * @param $data
     * @return $this
     * @throws \Exception
     */
    protected function saveToTable($data)
    {
        $this->getConnection()->beginTransaction();

        try {

            $this->getConnection()->insertMultiple(
                $this->getTable('subscription_profile_simulate_cache'),
                $data
            );
            $this->getConnection()->commit();

        } catch (\Exception $e) {
            $this->getConnection()->rollback();
            throw $e;
        }

        return $this;
    }

    /**
     * @return array
     */
    protected function getProfileCacheIds()
    {
        $select = $this->getConnection()->select()
            ->from($this->getTable('subscription_profile_simulate_cache'), ['profile_id']);
        return $this->getConnection()->fetchCol($select);
    }

    /**
     * Remove index entries before reindexation
     * @param array $ids
     * @return void
     */
    protected function cleanByIds($ids)
    {
        if ($ids) {
            $this->getConnection()->deleteFromSelect(
                $this->getConnection()
                    ->select()
                    ->from('subscription_profile_simulate_cache', 'profile_id')
                    ->distinct()
                    ->where('profile_id IN (?)', $ids),
                'subscription_profile_simulate_cache'
            );
        }
    }

    /**
     * @param $ids
     */
    public function removeDataProfile($ids)
    {
        $this->getConnection()->delete(
            $this->getTable('subscription_profile_simulate_cache'),
            ['profile_id IN (?)' => $ids]
        );
    }

    /**
     * @param $profileId
     */
    public function removeCacheInvalid($profileId)
    {
        $this->getConnection()->delete(
            $this->getTable('subscription_profile_simulate_cache'),
            ['profile_id = ?' => $profileId]
        );
    }
    /**
     * Clean data index
     *
     * @return $this
     */
    protected function deleteOldData()
    {
        $this->getConnection()->delete($this->getTable('subscription_profile_simulate_cache'));
        return $this;
    }

    /**
     * @param $id
     * @param int $deliveryNumber
     * @return array
     */
    public function loadSimulateDataByCustomerId($id, $deliveryNumber = 0)
    {
        $sql = $this->getConnection()
            ->select()
            ->from('subscription_profile_simulate_cache')
            ->where('customer_id = ?', $id)
            ->where('delivery_number = ?', $deliveryNumber);
        return $this->getConnection()->fetchAll($sql);
    }

    /**
     * Load Simulate Data By Profile Id
     *
     * @param $id
     * @param $deliveryNumber (0 is current delivery number)
     *
     * @return array
     */
    public function loadSimulateDataByProfileId($id, $deliveryNumber = 0)
    {
        $sql = $this->getConnection()
            ->select()
            ->from('subscription_profile_simulate_cache')
            ->where('profile_id = ?', $id)
            ->where('delivery_number = ?', $deliveryNumber);
        return $this->getConnection()->fetchRow($sql);
    }
}