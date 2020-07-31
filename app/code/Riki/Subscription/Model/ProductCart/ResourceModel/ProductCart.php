<?php
namespace Riki\Subscription\Model\ProductCart\ResourceModel;

class ProductCart extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected $timeslot;

    protected $logger;

    public function __construct(
        \Riki\TimeSlots\Model\TimeSlots $timeSlots,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        string $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->timeslot = $timeSlots;
        $this->logger = $logger;
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('subscription_profile_product_cart', 'cart_id');
    }

    public function getTimeSlotId($slotId)
    {
        $slot = $this->timeslot->load($slotId);
        if ($slot->getId()) {
            return true;
        }
        return false;
    }

    public function removeProductCart($profileId)
    {
        $this->getConnection()->delete(
            $this->getTable('subscription_profile_product_cart'),
            ['profile_id = ?' => $profileId]
        );
    }

    /**
     * validate address from subscription data before delete
     *
     * @param $addressId
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function validateAddress ($addressId)
    {
        $result = $this->checkAddressCanDelete($addressId);
        if(!$result)
        {
            $canDelete = $this->deleteAddressExistOnSubscriptionDisengagement($addressId);
            if($canDelete)
            {
                return $canDelete;
            }
        }
        return $result;
    }

    /**
     * @param $profileId
     * @return array
     */
    public function getSpotItemIds($profileId)
    {
        $select = $this->getConnection()->select()->from(
            $this->getTable('subscription_profile_product_cart'), ['product_id'])
            ->where('profile_id = ?', $profileId)
            ->where('is_spot = 1');
        return $this->getConnection()->fetchCol($select);
    }

    /**
     * @param $addressId
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function checkAddressCanDelete ($addressId)
    {
        $connection = $this->getConnection();

        $sqlSelect = $connection->select()->from(
            $this->getMainTable(), ['DISTINCT(profile_id)']
        );

        $sqlSelect->where(
            'shipping_address_id = ?', $addressId
        );

        $sqlSelect->orWhere(
            'billing_address_id = ?', $addressId
        );

        $listProfile = $connection->fetchCol($sqlSelect);

        if (!empty($listProfile)) {

            $getProfileMain = $connection->select()->from(
                $this->getTable('subscription_profile'), ['COUNT(profile_id)']
            )->where(
                'profile_id IN (?)', $listProfile
            )->where(
                '`type` IS NULL || `type` = ?', \Riki\Subscription\Model\Profile\Profile::SUBSCRIPTION_TYPE_TMP
            );

            $profileMain = $connection->fetchOne($getProfileMain);

            /*do not delete address of profile main*/
            if (!empty($profileMain)) {
                return false;
            }

            $getActiveProfileVersion = $connection->select()->from(
                $this->getTable('subscription_profile_version'), ['COUNT(id)']
            )->where(
                '`moved_to` IN (?)', $listProfile
            )->where(
                '`status` = ?' , \Riki\Subscription\Model\Version\Version::ACTIVE_STATUS
            );

            $activeProfileVersion = $connection->fetchOne($getActiveProfileVersion);

            /*do not delete address of profile version which is active*/
            if (!empty($activeProfileVersion)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $addressId
     * @return bool
     */
    public function deleteAddressExistOnSubscriptionDisengagement($addressId)
    {
        $connection = $this->getConnection();

        $sqlSelect = $connection->select()->from(
            ['main_table' => $connection->getTableName('subscription_profile')],
            ['profile_id', 'status', 'disengagement_date', 'disengagement_reason', 'disengagement_user']
        );
        $sqlSelect->joinLeft(
            ['c' => $this->getTable('subscription_profile_product_cart')],
            'main_table.profile_id  = c.profile_id',
            ['shipping_address_id', 'billing_address_id']
        );
        $sqlSelect->where(
            'c.shipping_address_id = ?', $addressId
        );

        $sqlSelect->orWhere(
            'c.billing_address_id = ?', $addressId
        );

        $listProfile = $connection->fetchAll($sqlSelect);

        $isDelete = false;
        if (is_array($listProfile) && count($listProfile) > 0) {
            $isDelete = true;
            foreach ($listProfile as $profile) {
                /**
                 * profile disengagement when status = 0 , disengagement_date, disengagement_reason, disengagement_user not null
                 */
                if ($profile['status'] != 0) {
                    $isDelete = false;
                    return $isDelete;
                } else if ($profile['disengagement_date'] == null || $profile['disengagement_reason'] == null || $profile['disengagement_user'] == null) {
                    $isDelete = false;
                    return $isDelete;
                }
            }
        }

        return $isDelete;
    }
    protected function _beforeSave(\Magento\Framework\Model\AbstractModel $object)
    {
        if ($object->getData('delivery_time_slot') != null and
            $object->getData('delivery_time_slot') != -1 and
            !$this->getTimeSlotId($object->getData('delivery_time_slot'))
        ) {
            $logData = [
                'label' => 'NED-2126',
                'profile_data' => $object->getData()
            ];

            $exception = new \Exception(json_encode($logData));
            $this->logger->debug($exception->getMessage(), ['NED-2126_trace' => $exception->getTraceAsString()]);
        }
        return parent::_beforeSave($object);
    }
}