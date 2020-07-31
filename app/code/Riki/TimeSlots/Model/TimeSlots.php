<?php

namespace Riki\TimeSlots\Model;

class TimeSlots extends \Magento\Framework\Model\AbstractModel
{

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Riki\TimeSlots\Model\ResourceModel\TimeSlots $resource,
        \Riki\TimeSlots\Model\ResourceModel\TimeSlots\Collection $resourceCollection
    )
    {
        parent::__construct($context, $registry, $resource, $resourceCollection);
    }

}