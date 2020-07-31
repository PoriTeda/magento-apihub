<?php

namespace Riki\SalesRule\Model;

class RulesApplier extends \Magento\SalesRule\Model\RulesApplier
{
    /**
     * Update applied rules breakdown data.
     *
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @param int[] $appliedRuleIds
     *
     * @return $this
     */
    public function setAppliedRuleIds(\Magento\Quote\Model\Quote\Item\AbstractItem $item, array $appliedRuleIds)
    {
        parent::setAppliedRuleIds($item, $appliedRuleIds);

        $currentInfo = $item->getAppliedRulesBreakdown();

        if (!empty($currentInfo)) {
            try {
                $currentInfo = \Zend_Json::decode($currentInfo);

                if (is_array($currentInfo)) {
                    foreach ($currentInfo as $ruleId => $amountData) {
                        if (!in_array($ruleId, $appliedRuleIds))
                            unset($currentInfo[$ruleId]);
                    }
                }

                $newInfo = count($currentInfo) > 0 ? \Zend_Json::encode($currentInfo) : null;

                $item->setAppliedRulesBreakdown($newInfo);

            } catch (\Exception $e) {
                $item->setAppliedRulesBreakdown($currentInfo);
            }
        }

        return $this;
    }
}
