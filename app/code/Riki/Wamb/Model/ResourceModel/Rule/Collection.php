<?php


namespace Riki\Wamb\Model\ResourceModel\Rule;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_init(\Riki\Wamb\Model\Rule::class, \Riki\Wamb\Model\ResourceModel\Rule::class);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $field
     * @param string $value
     * @param string $type
     *
     * @return $this
     */
    public function addFilter($field, $value, $type = 'and')
    {
        if ($field == 'course_id') {
            $ruleCourseTb = $this->getTable('riki_wamb_rule_course');
            $this->join(['rco' => $ruleCourseTb], 'rco.rule_id = main_table.rule_id', ['course_id']);
            $field = 'rco.course_id';
        }
        if ($field == 'category_id') {
            $ruleCategoryTb = $this->getTable('riki_wamb_rule_category');
            $this->join(['rca' => $ruleCategoryTb], 'rca.rule_id = main_table.rule_id', ['category_id']);
            $field = 'rca.category_id';
        }

        return parent::addFilter($field, $value, $type);
    }

    public function addFieldToFilter($field, $condition = null)
    {
        if ($field == 'course_id') {
            $ruleCourseTb = $this->getTable('riki_wamb_rule_course');
            $this->join(['rco' => $ruleCourseTb], 'rco.rule_id = main_table.rule_id', ['course_id']);
            $field = 'rco.course_id';
        }
        if ($field == 'category_id') {
            $ruleCategoryTb = $this->getTable('riki_wamb_rule_category');
            $this->join(['rca' => $ruleCategoryTb], 'rca.rule_id = main_table.rule_id', ['category_id']);
            $field = 'rca.category_id';
        }

        return parent::addFieldToFilter($field, $condition);
    }
}
