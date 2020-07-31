<?php

namespace Riki\SalesRule\Plugin;

class UpdateAppliedRulesInfo
{
    /**
     * @var \Riki\SalesRule\Model\AppliedRulesUpdater
     */
    protected $appliedRulesUpdater;

    /**
     * UpdateAppliedRulesInfo constructor.
     *
     * @param \Riki\SalesRule\Model\AppliedRulesUpdater $appliedRulesUpdater
     */
    public function __construct(
        \Riki\SalesRule\Model\AppliedRulesUpdater $appliedRulesUpdater
    )
    {
        $this->appliedRulesUpdater = $appliedRulesUpdater;
    }

    /**
     * @param \Magento\SalesRule\Model\Utility $subject
     * @param \Magento\SalesRule\Model\Rule\Action\Discount\Data $discountData
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @param $qty
     *
     * @return array
     */
    public function beforeMinFix(
        \Magento\SalesRule\Model\Utility $subject,
        \Magento\SalesRule\Model\Rule\Action\Discount\Data $discountData,
        \Magento\Quote\Model\Quote\Item\AbstractItem $item,
        $qty
    )
    {
        $this->appliedRulesUpdater
            ->updateItem($item, $discountData)
            ->reset();

        return [$discountData, $item, $qty];
    }
}
