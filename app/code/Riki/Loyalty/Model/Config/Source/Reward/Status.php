<?php

namespace Riki\Loyalty\Model\Config\Source\Reward;

use Magento\Framework\Data\OptionSourceInterface;
use Riki\Loyalty\Model\Reward;

class Status implements OptionSourceInterface
{
    /**
     * {@inheritDoc}
     */
    public function toOptionArray()
    {
        return [
            ['value' => Reward::STATUS_ERROR, 'label' => __('Error')],
            ['value' => Reward::STATUS_TENTATIVE, 'label' => __('Tentative')],
            ['value' => Reward::STATUS_REDEEMED, 'label' => __('Redeemed')],
            ['value' => Reward::STATUS_PENDING_APPROVAL, 'label' => __('Pending approval')],
            ['value' => Reward::STATUS_SHOPPING_POINT, 'label' => __('Shopping point')],
            ['value' => Reward::STATUS_CANCEL, 'label' => __('Cancel')]
        ];
    }
}