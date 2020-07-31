<?php
namespace Riki\Subscription\Model\ResourceModel;

use \Riki\Subscription\Model\ProductCart\Replace\Validator as ReplaceValidator;
use Riki\CreateProductAttributes\Model\Product\CaseDisplay;
use Riki\Shipment\Model\ResourceModel\Status\Options\Shipment as ShipmentStatus;
use Riki\SubscriptionCourse\Model\Course\Type as CourseType;

/**
 * Class Profile
 * @package Riki\Subscription\Model\ResourceModel
 */
class Profile extends \Riki\Subscription\Model\Profile\ResourceModel\Profile
{
    const PROFILE_STATUS_LAST_ORDER_NOT_CREATED = 0;
    const PROFILE_STATUS_LAST_ORDER_SHIPMENT_CREATED = 1;
    const PROFILE_STATUS_LAST_ORDER_SHIPMENT_EXPORTED = 2;
    const PROFILE_STATUS_LAST_ORDER_SHIPMENT_CANCELED = 3;

    /**
     * @var \Riki\Subscription\Model\Profile\ResourceModel\Profile\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * Profile constructor.
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param VersionFactory\VersionFactory $versionFactory
     * @param \Riki\Subscription\Logger\LoggerReplaceProduct $logger
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Riki\Subscription\Model\Profile\ResourceModel\Profile\CollectionFactory $collectionFactory
     * @param null $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Riki\Subscription\Model\Version\VersionFactory $versionFactory,
        \Riki\Subscription\Logger\LoggerReplaceProduct $logger,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Riki\Subscription\Model\Profile\ResourceModel\Profile\CollectionFactory $collectionFactory,
        $connectionName = null
    ) {
        $this->collectionFactory = $collectionFactory;
        parent::__construct(
            $context,
            $versionFactory,
            $logger,
            $productFactory,
            $connectionName
        );
    }

    /**
     * @param int $profileId
     * @return array
     */
    public function getShippingAddress($profileId = 0)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from(['sp' => $this->getTable('subscription_profile_product_cart')], ['sp.shipping_address_id'])
            ->where('sp.profile_id = ?', $profileId);
        return $connection->fetchAll($select);
    }

    /**
     * replace discontinue product
     *
     * @param $oldId
     * @param $newId
     * @param $bundleAdditionalInfo
     * @return $this
     * @throws \Exception
     */
    public function replaceProduct($oldId, $newId, $bundleAdditionalInfo = [])
    {
        $productTable = $this->getTable('subscription_profile_product_cart');
        $connection = $this->getConnection();
        // select course will update
        $profileIds = $this->getProfileIdByProductId($oldId);
        $productType = $this->getProductById($newId)->getTypeId();
        if (!empty($profileIds)) {
            try {
                $connection->beginTransaction();
                $bind = ['product_id' => $newId, 'product_type' => $productType];
                $condition = ['product_id=?' => $oldId];
                if (isset($bundleAdditionalInfo['direction'])) {
                    list($bind, $condition) = $this->buildBundleUpdateData(
                        $oldId,
                        $bind,
                        $condition,
                        $bundleAdditionalInfo
                    );
                }
                // update
                $connection->update(
                    $productTable,
                    $bind,
                    $condition
                );
                // delete product children of bundle
                $connection->delete(
                    $productTable,
                    'parent_item_id ='.$oldId
                );
                $connection->commit();
                $this->logger->info('Updated profile ids: "'.implode(', ', $profileIds).'".');
            } catch (\Exception $e) {
                $connection->rollBack();
                throw $e;
            }
        }
        return $this;
    }

    /**
     * @param $oldId
     * @param $bind
     * @param $condition
     * @param $bundleAdditionalInfo
     * @return array
     */
    protected function buildBundleUpdateData($oldId, $bind, $condition, $bundleAdditionalInfo)
    {
        $simpleUnitQty = $bundleAdditionalInfo['simple_unit_qty'];
        switch ($bundleAdditionalInfo['direction']) {
            case ReplaceValidator::SIMPLE_TO_BUNDLE_DIRECTION_TYPE:
                $phrase1 = "(unit_case='EA' AND unit_qty=1 AND MOD(qty,$simpleUnitQty)=0)";
                $phrase2 = "(unit_case='CS' AND unit_qty=$simpleUnitQty AND MOD(qty,$simpleUnitQty)=0)";
                $condition = new \Zend_Db_Expr("product_id=$oldId AND ($phrase1 OR $phrase2)");
                $bind['qty'] = new \Zend_Db_Expr("qty/$simpleUnitQty");
                $bind['unit_qty'] = 1;
                $bind['unit_case'] = CaseDisplay::PROFILE_UNIT_PIECE;
                break;
            case ReplaceValidator::BUNDLE_TO_SIMPLE_DIRECTION_TYPE:
                $condition['unit_case=?'] = CaseDisplay::PROFILE_UNIT_PIECE;
                $condition['unit_qty=?'] = 1;
                $bind['unit_qty'] = 1;
                $bind['qty'] = new \Zend_Db_Expr("qty*$simpleUnitQty");
                break;
            default:
                $condition = ['product_id=?' => $oldId];
                break;
        }
        return [$bind, $condition];
    }

    /**
     * Delete profile product
     *
     * @param $productId
     * @return array
     * @throws \Exception
     */
    public function deleteProfileProduct($productId)
    {
        $profileProductTable = $this->getTable('subscription_profile_product_cart');
        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $connection */
        $connection = $this->getConnection();
        // select course will update
        $profileIds = $this->getProfileListByProductId($productId);

        /*list of profile has deleted product*/
        $deleteProfile = [];

        /*list of profile cannot delete product - profile only contain this product*/
        $noneDelete = [];

        if ($profileIds) {
            foreach ($profileIds as $id) {
                $canDelete = $this->canDeleteProfileProduct($id);
                if ($canDelete) {
                    array_push($deleteProfile, $id);
                } else {
                    array_push($noneDelete, $id);
                }
            }

            if ($deleteProfile) {
                try {
                    $connection->beginTransaction();

                    /* delete product from profile */
                    $connection->delete($profileProductTable, [
                        'product_id = ?' => $productId,
                        'profile_id IN(?)' => $deleteProfile
                    ]);

                    /* delete bundle item from profile if product type is bundle*/
                    $connection->delete($profileProductTable, [
                        'parent_item_id = ?' => $productId, 'profile_id IN(?)' => $deleteProfile
                    ]);

                    $connection->commit();

                    $this->logger->info('Delete product id "'.$productId.'" from profile ids: "'.implode(', ', $deleteProfile).'".');
                } catch (\Exception $e) {
                    $connection->rollBack();
                    throw $e;
                }
            }

            if ($noneDelete) {
                $this->logger->info(
                    'Cannot delete product id "'.
                    $productId
                    .'" from profile ids: "'.
                    implode(', ', $noneDelete).'".'
                );
            }
        }

        return [
            'success' => $deleteProfile,
            'fail' => $noneDelete
        ];
    }

    /**
     * can delete a product on profile
     *      profile must have more than one product
     *
     * @param $profileId
     * @return bool
     */
    public function canDeleteProfileProduct($profileId)
    {
        /*cannot delete product from hanpukai profile*/
        if ($this->isHanpukaiProfile($profileId)) {
            return false;
        }

        $connection = $this->getConnection();

        $productCartTable = $this->getTable('subscription_profile_product_cart');

        $profileProduct = $connection->fetchCol(
            $connection->select()->from(
                $productCartTable,
                ['product_id']
            )->where(
                'profile_id = :profile_id AND (parent_item_id is NULL || parent_item_id = :parent_id)'
            ),
            [
                ':profile_id' => $profileId,
                /*dont count bundle item*/
                ':parent_id' => 0
            ]
        );

        /*profile contain more than one product can be delete*/
        if (!empty($profileProduct) && count($profileProduct) >= 2) {
            return true;
        }

        return false;
    }

    /**
     * @param $id string
     * @return boolean|array
     * [
     *     ["profile_id"]=>"17"
     *     ["customer_id"]=>"1"
     * ]
     */
    public function getCustomersHaveProduct($id)
    {
        $connection = $this->getConnection();

        $profileIds = $this->getProfileIdByProductId($id);

        $select = $connection->select()
            ->from($this->getTable('subscription_profile'), ['profile_id', 'customer_id'])
            ->where('profile_id IN (?)', $profileIds);
        $data = $connection->fetchAll($select);

        return $data;
    }

    /**
     * get customer by profile list
     *
     * @param array $profileList
     * @return array
     */
    public function getCustomerByProfileList($profileList)
    {
        $connection = $this->getConnection();

        $select = $connection->select()
            ->from($this->getTable('subscription_profile'), ['profile_id', 'customer_id'])
            ->where('profile_id IN (?)', $profileList);

        $data = $connection->fetchAll($select);

        return $data;
    }

    public function getProfileIdByProductId($id)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from(['p' => $this->getTable('subscription_profile_product_cart')], [])
            // we need to get main_profile_id, not profile_version_id
            ->joinLeft(
                ['v' => $this->getTable('subscription_profile_version')],
                'v.moved_to=p.profile_id',
                ['profile_id' => 'IFNULL(v.rollback_id,p.profile_id)']
            )
            ->where('product_id = ?', $id)
            ->group('profile_id');

        $profileIds = $connection->fetchCol($select);

        return $profileIds;
    }

    /**
     * @param $profileId
     * @return bool
     */
    public function isHanpukaiProfile($profileId)
    {
        $connection = $this->getConnection();

        $select = $connection->select()
            ->from(['c' => $this->getTable('subscription_course')], 'subscription_type')
            ->joinLeft(
                ['p' => $this->getTable('subscription_profile')],
                'c.course_id=p.course_id'
            )->where(
                'p.profile_id = ?',
                $profileId
            )->group('c.course_id');

        $profileInfo = $connection->fetchRow($select);

        if ($profileInfo
            && $profileInfo['subscription_type'] == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI) {
            return true;
        }

        return false;
    }

    /**
     * @param $id
     * @return array
     */
    public function getProfileByCourse($courseId, $frequencyId)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from(['p' => $this->getTable('subscription_profile')], 'profile_id')
            ->joinLeft(
                ['f' => $this->getTable('subscription_frequency')],
                'p.frequency_interval = f.frequency_interval and p.frequency_unit = f.frequency_unit'
            )
            ->where('p.course_id = ?', $courseId)
            ->where('f.frequency_id = ?', $frequencyId);
        $profile = $connection->fetchCol($select);
        // we need to get all profile includes: main and version to update
        return $profile;
    }

    /**
     * @param $id
     * @return array
     */
    public function getProfileListByProductId($id)
    {
        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $connection */
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable('subscription_profile_product_cart'), ['DISTINCT(profile_id)'])
            ->where('product_id = ?', $id);

        $profileIds = $connection->fetchCol($select);

        return $profileIds;
    }

    /**
     * @param $customerId
     * @param null $profileId
     * @param null $includedStatus
     * @return mixed
     */
    public function getCustomerSubscriptionProfiles($customerId, $profileId = null, $includedStatus = null)
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('customer_id', $customerId);
        if ($profileId) {
            $collection->addFieldToFilter('profile_id', $profileId);
        }
        if (isset($includedStatus)) {
            $collection->addFieldToFilter('status', $includedStatus);
        } else {
            /*
             *  Get only active profile
             */
            $collection->addFieldToFilter('main_table.disengagement_date', ['null' => true]);
            $collection->addFieldToFilter('main_table.disengagement_reason', ['null' => true]);
            $collection->addFieldToFilter('main_table.disengagement_user', ['null' => true]);
            $collection->addFieldToFilter(
                'main_table.status',
                \Riki\Subscription\Model\Profile\Profile::STATUS_ENABLED
            );
        }
        //Not include temp profile
        $collection->addFieldToFilter('main_table.type', ['null' => true]);
        $collection->getSelect()->joinInner(
            ['subscription_course' => 'subscription_course'],
            'main_table.course_id = subscription_course.course_id',
            [
                'subscription_course.course_name as subscription_course_name',
                'subscription_course.course_code',
                'subscription_course.allow_skip_next_delivery',
                'subscription_course.allow_change_product',
                'subscription_course.is_allow_cancel_from_frontend',
                'subscription_course.minimum_order_times',
            ]
        );
        $collection->addFieldToFilter(
            'main_table.status',
            \Riki\Subscription\Model\Profile\Profile::STATUS_ENABLED
        );
        $collection->addFieldToFilter(
            'subscription_course.subscription_type',
            ['nin' => [CourseType::TYPE_HANPUKAI, CourseType::TYPE_MONTHLY_FEE]]
        );
        $collection->addOrder(
            'main_table.profile_id',
            \Magento\Framework\Api\SortOrder::SORT_DESC
        );
        return $collection;
    }

    /**
     * The last shipment delivery date of the profile
     *
     * @param int $profileId
     * @return string
     */
    public function getLastShipmentDeliveryDateOfProfile(int $profileId)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from(
                ['main_table' => $this->getTable('sales_order')],
                [
                    'max(sales_order_item.delivery_date)'
                ]
            )
            ->joinInner(
                ['sales_order_item' => $this->getTable('sales_order_item')],
                'sales_order_item.order_id = main_table.entity_id',
                ''
            )
            ->where('main_table.subscription_profile_id =?', $profileId);
        return $connection->fetchOne($select);
    }

    /**
     * Get profiles to send to KSS
     *
     * @param $customerId
     * @return array
     */
    public function getActiveProfileIds($customerId)
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter('customer_id', $customerId);
        $collection->getSelect()
            ->join(
                ['sc' => 'subscription_course'],
                'sc.course_id = main_table.course_id',
                'sc.subscription_type'
            )->where(
                'main_table.status=?',
                \Riki\Subscription\Model\Profile\Profile::STATUS_ENABLED
            )->where(
                'main_table.type IS NULL'
            )->where(
                'sc.subscription_type IN(?)',
                [CourseType::TYPE_SUBSCRIPTION, CourseType::TYPE_HANPUKAI]
            );
        $hanpukaiIds = [];
        $subscriptionIds = [];
        foreach ($collection as $profile) {
            if ($profile->getSubscriptionType() == CourseType::TYPE_SUBSCRIPTION) {
                $subscriptionIds[] = $profile->getId();
            } elseif ($profile->getSubscriptionType() == CourseType::TYPE_HANPUKAI) {
                $hanpukaiIds[] = $profile->getId();
            }
        }
        $result = [
            CourseType::TYPE_SUBSCRIPTION => $subscriptionIds,
            CourseType::TYPE_HANPUKAI => $hanpukaiIds,
        ];
        return $result;
    }
}
