<?php

namespace Riki\Subscription\Model\ResourceModel\Landing\Page\Grid;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

class Collection extends SearchResult
{
    /**
     * @param array|string $field
     * @param null $condition
     * @return SearchResult
     */
    public function addFieldToFilter($field, $condition = null)
    {
        switch($field){
            case 'landing_page_id':
                $field = 'main_table.landing_page_id';
                break;
            case 'course_ids':
                $field = 'excluded_course_table.course_id';
                $this->getSelect()->joinLeft(
                    ['excluded_course_table' => $this->getTable('subscription_landing_exclude_course')],
                    'main_table.landing_page_id = excluded_course_table.landing_page_id',
                    ''
                )->distinct(true);
                break;
        }
        return parent::addFieldToFilter($field, $condition);
    }
}
