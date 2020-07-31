<?php

namespace Riki\Loyalty\Model\Config\Source\Reward;

use Magento\Framework\Data\OptionSourceInterface;

use Riki\Loyalty\Model\ConsumerDb\ShoppingPoint;

class Type implements OptionSourceInterface
{
    /**
     * {@inheritDoc}
     */
    public function toOptionArray()
    {
        return [
            ['value' => ShoppingPoint::ISSUE_TYPE_PURCHASE, 'label' => __('On purchase')],
            ['value' => ShoppingPoint::ISSUE_TYPE_REVIEW, 'label' => __('Product review')],
            ['value' => ShoppingPoint::ISSUE_TYPE_QUESTION, 'label' => __('Questionnaire')],
            ['value' => ShoppingPoint::ISSUE_TYPE_ADJUSTMENT, 'label' => __('Adjustment')],
            ['value' => ShoppingPoint::ISSUE_TYPE_REGISTER, 'label' => __('Member registration')],
            ['value' => ShoppingPoint::ISSUE_TYPE_FREE_GIFT, 'label' => __('Free gift exchange')],
            ['value' => ShoppingPoint::ISSUE_TYPE_DISCOUNT, 'label' => __('Discount available')],
            ['value' => ShoppingPoint::ISSUE_TYPE_SITE_VISIT, 'label' => __('Site visit')],
            ['value' => ShoppingPoint::ISSUE_TYPE_GAME, 'label' => __('Game')],
            ['value' => ShoppingPoint::ISSUE_TYPE_CAMPAIGN, 'label' => __('Campaign')],
            ['value' => ShoppingPoint::ISSUE_TYPE_CONTENT_USAGE, 'label' => __('Content usage')],
            ['value' => ShoppingPoint::ISSUE_TYPE_POINT_EXCHANGE, 'label' => __('Point exchange')],
            ['value' => ShoppingPoint::ISSUE_TYPE_OTHER, 'label' => __('Other')]
        ];
    }

    /**
     * @param $value
     * @return string
     */
    public function getTitleByValue($value){
        foreach($this->toOptionArray() as $option){
            if($value == $option['value'])
                return $option['label'];
        }

        return '';
    }
}