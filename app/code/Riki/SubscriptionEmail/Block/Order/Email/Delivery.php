<?php

namespace Riki\SubscriptionEmail\Block\Order\Email;

use Magento\Framework\View\Element\Template\Context;
use Riki\TimeSlots\Model\TimeSlots;

class Delivery extends \Magento\Sales\Block\Items\AbstractItems
{
    /* @var \Riki\TimeSlots\Model\TimeSlots */
    protected $timeSlotModel;

    public function __construct(
        TimeSlots $timeSlots,
        Context $context,
        array $data = []
    ) {
        $this->timeSlotModel = $timeSlots;
        parent::__construct($context, $data);
    }

    public function getSlotName($timeSlotId)
    {
        $timeSlotObj = $this->timeSlotModel->load($timeSlotId);
        if ($timeSlotObj && $timeSlotObj->getId())
        {
            return $timeSlotObj->getData('slot_name');
        } else {
            return '';
        }
    }
}
