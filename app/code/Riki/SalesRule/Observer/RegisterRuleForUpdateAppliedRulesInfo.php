<?php

namespace Riki\SalesRule\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class RegisterRuleForUpdateAppliedRulesInfo implements ObserverInterface
{
    /**
     * @var \Riki\SalesRule\Model\AppliedRulesUpdater
     */
    protected $appliedRulesUpdater;

    /**
     * RegisterRuleForUpdateAppliedRulesInfo constructor.
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
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->appliedRulesUpdater->registerRule(
            $observer->getEvent()->getData('rule')
        );
    }
}
