<?php
namespace Riki\Chirashi\Model\CustomerSegment\Segment\Condition\Product\Combine;

class History extends \Magento\CustomerSegment\Model\Segment\Condition\Product\Combine\History
{
    /**
     * Limit select by website with joining to store table
     *
     * @param \Magento\Framework\DB\Select $select
     * @param int|\Zend_Db_Expr $website
     * @param string $storeIdField
     * @return $this
     */
    protected function _limitByStoreWebsite(\Magento\Framework\DB\Select $select, $website, $storeIdField)
    {
        $storeTable = $this->getResource()->getTable('store');
        if (is_numeric($website) || is_array($website)) {
            $storeSelect = $this->getResource()->createSelect();
            $storeSelect->from(
                ['store' => $storeTable],
                ['store.store_id']
            )->where('store.website_id IN (?)', $website);
            $storeIds = $this->getResource()->getConnection()->fetchCol($storeSelect);
            $select->where($storeIdField . ' IN (?)', implode(',', $storeIds));
        } else {
            $select->join(
                ['store' => $storeTable],
                $storeIdField . '=store.store_id',
                []
            )->where(
                'store.website_id IN (?)',
                $website
            );
        }
        return $this;
    }
}
