<?php

namespace Riki\PageCache\Plugin;

class CastGroupIdToInt
{
    /**
     * Make sure data type is persistent as it affects result in generate X-Magento-Vary.
     * Currently $groupId can be string or int. We will cast $groupId to int.
     *
     * @param \Magento\Customer\Model\Session $subject
     * @param $groupId
     *
     * @return int
     */
    public function afterGetCustomerGroupId($subject, $groupId)
    {
        return (int)$groupId;
    }
}
