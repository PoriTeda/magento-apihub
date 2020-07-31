<?php
namespace Riki\Subscription\Model\ResourceModel\Multiple\Category;

/**
 * Class Campaign
 * @package Riki\Subscription\Model\ResourceModel\Multiple\Category
 */
class Campaign extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var array
     */
    protected $associatedEntitiesMap = [
        'category_ids' => [
            'target_field' => 'category_ids',
            'associations_table' => 'subscription_multiple_category_campaign_category',
            'campaign_id_field' => 'campaign_id',
            'entity_id_field' => 'category_id',
        ],
        'course_ids' => [
            'target_field' => 'course_ids',
            'associations_table' => 'subscription_multiple_category_campaign_excluded_course',
            'campaign_id_field' => 'campaign_id',
            'entity_id_field' => 'course_id',
        ],];

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('subscription_multiple_category_campaign', 'campaign_id');
    }
    /**
     * {@inheritdoc}
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     *
     * @return $this
     */
    protected function _beforeDelete(\Magento\Framework\Model\AbstractModel $object)
    {
        $this->deleteAssociatedEntitiesMap($object);
        return parent::_beforeDelete($object);
    }

    /**
     * {@inheritdoc}
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     *
     * @return  $this
     */
    protected function _afterSave(\Magento\Framework\Model\AbstractModel $object)
    {
        $this->saveAssociatedEntitiesMap($object);
        return parent::_afterSave($object);
    }

    /**
     * {@inheritdoc}
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     */
    protected function deleteAssociatedEntitiesMap(\Magento\Framework\Model\AbstractModel $object)
    {
        foreach ($this->associatedEntitiesMap as $map) {
            $conn = $this->getConnection();
            $conn->delete(
                $conn->getTableName($map['associations_table']),
                "{$map['campaign_id_field']} = {$object->getId()}"
            );
        }
    }
    /**
     * Process $associatedEntitiesMap
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     */
    protected function saveAssociatedEntitiesMap(\Magento\Framework\Model\AbstractModel $object)
    {
        foreach ($this->associatedEntitiesMap as $map) {
            if (!$object->dataHasChangedFor($map['target_field'])) {
                continue;
            }

            $orig = (array)$object->getOrigData($map['target_field']);
            $current = (array)$object->getData($map['target_field']);

            $delete = array_diff($orig, $current);
            $deleteData = implode(',', $delete);
            $insertData = array_diff($current, $orig);
            $conn = $this->getConnection();
            if ($insertData) {
                foreach ($insertData as $catId) {
                    $insertRow = [
                        $map['campaign_id_field'] => $object->getId(),
                        $map['entity_id_field'] => $catId,
                    ];
                    $conn->insertOnDuplicate(
                        $conn->getTableName($map['associations_table']),
                        $insertRow
                    );
                }
            }
            if ($deleteData) {
                $conn->delete(
                    $conn->getTableName($map['associations_table']),
                    "{$map['campaign_id_field']} = {$object->getId()} and "
                    . "{$map['entity_id_field']} IN ({$deleteData})"
                );
            }
        }
    }
}
