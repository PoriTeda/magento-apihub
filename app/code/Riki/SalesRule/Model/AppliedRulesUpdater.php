<?php

namespace Riki\SalesRule\Model;

class AppliedRulesUpdater
{
    /**
     * @var \Magento\SalesRule\Model\Rule|null
     */
    protected $rule;

    /**
     * @param \Magento\SalesRule\Model\Rule $rule
     *
     * @return $this
     */
    public function registerRule(\Magento\SalesRule\Model\Rule $rule)
    {
        $this->rule = $rule;

        return $this;
    }

    /**
     * @return $this
     */
    public function reset()
    {
        $this->rule = null;

        return $this;
    }

    /**
     * @param $item
     * @param $discountData
     *
     * @return $this
     */
    public function updateItem(
        \Magento\Quote\Model\Quote\Item\AbstractItem $item,
        \Magento\SalesRule\Model\Rule\Action\Discount\Data $discountData
    )
    {
        if (is_null($this->rule)) {
            return $this;
        }

        if ($discountData->getAmount() <= 0) {
            return $this;
        }

        $currentInfo = $item->getAppliedRulesBreakdown();
        $ruleId = $this->rule->getId();

        try {
            if (!empty($currentInfo)) {
                $currentInfo = \Zend_Json::decode($currentInfo);
            } else {
                $currentInfo = [];
            }

            $currentInfo[$ruleId] = $discountData->getAmount();

            $newInfo = \Zend_Json::encode($currentInfo);
        } catch (\Exception $e) {
            $newInfo = $item->getAppliedRulesBreakdown();
        }

        $item->setAppliedRulesBreakdown($newInfo);

        return $this;
    }
}
