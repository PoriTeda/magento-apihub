<?php
namespace Riki\Wamb\Model\ResourceModel;

class Rule extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var array
     */
    protected $_associatedEntitiesMap = [
        'rule_course' => [
            'target_field' => 'course_ids',
            'associations_table' => 'riki_wamb_rule_course',
            'rule_id_field' => 'rule_id',
            'entity_id_field' => 'course_id',

        ],
        'rule_category' => [
            'target_field' => 'category_ids',
            'associations_table' => 'riki_wamb_rule_category',
            'rule_id_field' => 'rule_id',
            'entity_id_field' => 'category_id',

        ],];

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_init('riki_wamb_rule', 'rule_id');
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
        foreach ($this->_associatedEntitiesMap as $map) {
            $conn = $this->getConnection();
            $conn->delete($conn->getTableName($map['associations_table']), "{$map['rule_id_field']} = {$object->getId()}");
        }
    }

    /**
     * Process $_associatedEntitiesMap
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     */
    protected function saveAssociatedEntitiesMap(\Magento\Framework\Model\AbstractModel $object)
    {
        foreach ($this->_associatedEntitiesMap as $map) {
            if (!$object->dataHasChangedFor($map['target_field'])) {
                continue;
            }

            $orig = (array)$object->getOrigData($map['target_field']);
            $current = (array)$object->getData($map['target_field']);

            $delete = array_diff($orig, $current);
            $deleteData = implode(',', $delete);
            $insert = array_diff($current, $orig);
            $insertData = array_map(
                function ($v) use ($object, $map) {
                    return [
                        $map['rule_id_field'] => $object->getId(),
                        $map['entity_id_field'] => $v
                    ];
                },
                $insert
            );

            $conn = $this->getConnection();
            if ($insertData) {
                $conn->insertOnDuplicate(
                    $conn->getTableName($map['associations_table']),
                    $insertData,
                    [$map['rule_id_field'], $map['entity_id_field']]
                );
            }
            if ($deleteData) {
                $conn->delete(
                    $conn->getTableName($map['associations_table']),
                    "{$map['rule_id_field']} = {$object->getId()} and "
                    . "{$map['entity_id_field']} IN ({$deleteData})"
                );
            }
        }
    }
}
