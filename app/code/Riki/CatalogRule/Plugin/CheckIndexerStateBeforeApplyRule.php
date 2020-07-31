<?php

namespace Riki\CatalogRule\Plugin;

class CheckIndexerStateBeforeApplyRule
{
    /**
     * @var \Magento\CatalogRule\Model\Indexer\Rule\RuleProductProcessor
     */
    private $ruleProductProcessor;

    /**
     * CheckIndexerStateBeforeApplyRule constructor.
     *
     * @param \Magento\CatalogRule\Model\Indexer\Rule\RuleProductProcessor $ruleProductProcessor
     */
    public function __construct(
        \Magento\CatalogRule\Model\Indexer\Rule\RuleProductProcessor $ruleProductProcessor
    ) {
        $this->ruleProductProcessor = $ruleProductProcessor;
    }

    /**
     * @param \Magento\CatalogRule\Model\Rule\Job $subject
     * @param \Closure $proceed
     *
     * @return void
     */
    public function aroundApplyAll(\Magento\CatalogRule\Model\Rule\Job $subject, \Closure $proceed)
    {
        $state = $this->ruleProductProcessor->getIndexer()->getState();
        if ($state->getStatus() == \Magento\Framework\Indexer\StateInterface::STATUS_WORKING) {
            $subject->setError(__('Catalog rule product indexer is running, please try again after indexer finishes.'));
            return;
        }

        $proceed();
    }
}
