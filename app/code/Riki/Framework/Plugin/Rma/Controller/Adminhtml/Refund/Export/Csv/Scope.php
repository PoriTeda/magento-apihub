<?php
namespace Riki\Framework\Plugin\Rma\Controller\Adminhtml\Refund\Export\Csv;

class Scope
{
    /**
     * @var \Riki\Framework\Helper\Scope
     */
    protected $scopeHelper;

    /**
     * Scope constructor.
     *
     * @param \Riki\Framework\Helper\Scope $scopeHelper
     */
    public function __construct(
        \Riki\Framework\Helper\Scope $scopeHelper
    ) {
        $this->scopeHelper = $scopeHelper;
    }

    /**
     * Scoping function
     *
     * @param \Riki\Rma\Controller\Adminhtml\Refund\Export\Csv $subject
     * @param \Closure $proceed
     *
     * @return mixed
     */
    public function aroundExecute(\Riki\Rma\Controller\Adminhtml\Refund\Export\Csv $subject, \Closure $proceed)
    {
        $key = \Riki\Rma\Controller\Adminhtml\Refund\Export\Csv::class . '::execute';
        $this->scopeHelper->inFunction($key);
        $result = $proceed();
        $this->scopeHelper->outFunction($key);

        return $result;
    }
}