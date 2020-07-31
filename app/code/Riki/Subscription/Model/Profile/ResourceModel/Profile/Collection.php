<?php
namespace Riki\Subscription\Model\Profile\ResourceModel\Profile;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    protected $_eventPrefix = 'subscription_profile';
    /**
     * @var string
     */
    protected $_idFieldName = 'profile_id';

    protected $_versionFactory;

    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Riki\Subscription\Model\Version\VersionFactory $versionFactory)
    {

        $this->_versionFactory = $versionFactory;

        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, null, null);
    }

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Subscription\Model\Profile\Profile', 'Riki\Subscription\Model\Profile\ResourceModel\Profile');
        $this->_map['fields']['profile_id'] = 'main_table.profile_id';
    }

    /**
     * Just load main item don't load version
     */
    protected function _renderFiltersBefore()
    {
        if($this->getFlag('original') != 1) {
            if (!isset($this->_joinedTables['subscription_profile_version'])
                && !isset($this->_joinedTables['subscription_profile_link'])
            ) {
                // Get list move_to in table revision
                $this->_filters = [
                    0 => [
                        'type' => 'string',
                        'value' => 'main_table.type is null',
                    ]
                ];
            }
        }
    }

    protected function _afterLoadData() {

        $objectManager =\Magento\Framework\App\ObjectManager::getInstance();
        // Load again with revision
        foreach($this->_data as $key => $_arr) {


            $objProfileHelper = $objectManager->get('Riki\Subscription\Helper\Profile\Data');
            $objProfile = $objProfileHelper->load($_arr['profile_id']);

            $this->_data[$key] = $objProfile->getData() + $this->_data[$key];
            $this->_data[$key]['profile_id'] = $_arr['profile_id']; // Show revision data but with real id
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param array|string $field
     * @param null $condition
     *
     * @return $this
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if ((is_array($field) && in_array('tmp_parent_profile_id', $field))
            || $field == 'tmp_parent_profile_id'
        ) {
            $this->join(
                ['subscription_profile_link' => $this->getTable('subscription_profile_link')],
                'subscription_profile_link.linked_profile_id = main_table.profile_id',
                []
            );

            $field = 'subscription_profile_link.profile_id';
        } elseif ((is_array($field) && in_array('version_parent_profile_id', $field))
            || $field == 'version_parent_profile_id'
        ) {
            $this->join(
                ['subscription_profile_version' => $this->getTable('subscription_profile_version')],
                'subscription_profile_version.moved_to = main_table.profile_id',
                []
            );

            $field = 'subscription_profile_version.rollback_id';
        }

        parent::addFieldToFilter($field, $condition);

        return $this;
    }
}