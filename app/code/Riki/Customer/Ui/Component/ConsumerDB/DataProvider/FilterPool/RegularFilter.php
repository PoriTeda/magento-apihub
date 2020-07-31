<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Riki\Customer\Ui\Component\ConsumerDB\DataProvider\FilterPool;


use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Api\Filter;

/**
 * Class RegularFilter
 */
class RegularFilter
{
    /**
     * Apply regular filters like collection filters
     *
     * @param AbstractDb $collection
     * @param Filter $filter
     * @return void
     */
    public function apply(\Riki\Customer\Model\ResourceModel\ConsumerDB\Collection $collection, Filter $filter)
    {
        $collection->addFilter($filter->getField(), $filter->getValue(),$filter->getConditionType());
    }
}
