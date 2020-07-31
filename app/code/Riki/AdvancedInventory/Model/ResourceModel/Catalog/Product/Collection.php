<?php
namespace Riki\AdvancedInventory\Model\ResourceModel\Catalog\Product;

class Collection extends \Magento\Catalog\Model\ResourceModel\Product\Collection
{
    public function addAttributeToSort($attribute, $dir = \Magento\Catalog\Model\ResourceModel\Product\Collection::SORT_ORDER_ASC)
    {
        if ($attribute == 'as_available_qty') {
            $this->getSelect()->order($attribute . ' ' . $dir);
            return $this;
        }

        return parent::addAttributeToSort($attribute, $dir);
    }


    /**
     * Get SQL for get record count
     *
     * @param null $select
     * @param bool $resetLeftJoins
     * @return \Magento\Framework\DB\Select
     */
    protected function _getSelectCountSql($select = null, $resetLeftJoins = true)
    {
        $this->_renderFilters();
        $countSelect = is_null($select) ? $this->_getClearSelect() : $this->_buildClearSelect($select);

        if (array_key_exists('at_as_available_qty', $this->getSelect()->getPart('from'))) {

            $countSelect->columns('e.entity_id');

            $countSelect = $this->getConnection()
                ->select()
                ->from(['u' => $countSelect], ['COUNT(DISTINCT u.entity_id)']);

        } else {
            $countSelect->columns('COUNT(DISTINCT e.entity_id)');
            if ($resetLeftJoins) {
                $countSelect->resetJoinLeft();
            }
        }

        return $countSelect;
    }
}
