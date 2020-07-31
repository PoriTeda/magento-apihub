<?php

namespace Riki\Subscription\Model\ResourceModel\Multiple\Category\Campaign\Grid;

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
            case 'campaign_id':
                $field = 'main_table.campaign_id';
                break;
            case 'course_ids':
                $field = 'excluded_course_table.course_id';
                $this->getSelect()->joinLeft(
                    ['excluded_course_table' => $this->getTable('subscription_multiple_category_campaign_excluded_course')],
                    'main_table.campaign_id = excluded_course_table.campaign_id',
                    ''
                )->distinct(true);
                break;
        }
        return parent::addFieldToFilter($field, $condition);
    }
}
