<?php

namespace Riki\Sales\Model\Config\Source;

use Riki\Framework\Model\Source\AbstractOption;
use Riki\Sales\Model\ShippingReason;

/**
 * Class ShippingReasonStatus
 * @package Riki\Sales\Model\Config\Source
 */
class ShippingReasonStatus extends AbstractOption
{
    /**
     * @var ShippingReason
     */
    protected $reason;

    /**
     * ShippingReasonStatus constructor.
     * @param ShippingReason $reason
     */
    public function __construct(
        ShippingReason $reason
    )
    {
        $this->reason = $reason;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        $options = parent::toOptionArray();
        array_unshift($options, ['label' => __('Not allowed'), 'value' => '']);

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function prepare()
    {
        $options = [];
        foreach ($this->reason->getAvailableStatuses() as $id => $status) {
            $options[$id] = $status;
        }

        return $options;
    }
}
