<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Model\ResourceModel;

/**
 * Gift registry entity registrants resource model
 *
 * @api
 * @since 100.0.2
 */
class Person extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Resource model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('magento_giftregistry_person', 'person_id');
    }

    /**
     * Serialization for custom attributes
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     */
    protected function _beforeSave(\Magento\Framework\Model\AbstractModel $object)
    {
        $object->setCustomValues($this->getSerializer()->serialize($object->getCustom()));
        return parent::_beforeSave($object);
    }

    /**
     * De-serialization for custom attributes
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     */
    protected function _afterLoad(\Magento\Framework\Model\AbstractModel $object)
    {
        $object->setCustom($this->getSerializer()->unserialize($object->getCustomValues()));
        return parent::_afterLoad($object);
    }

    /**
     * Delete orphan persons
     *
     * @param int $entityId
     * @param array $personLeft - records which should not be deleted
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function deleteOrphan($entityId, $personLeft = [])
    {
        $connection = $this->getConnection();
        $condition = [];
        $condition[] = $connection->quoteInto('entity_id = ?', (int)$entityId);
        if (is_array($personLeft) && !empty($personLeft)) {
            $condition[] = $connection->quoteInto('person_id NOT IN (?)', $personLeft);
        }
        $connection->delete($this->getMainTable(), $condition);

        return $this;
    }
}
