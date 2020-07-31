<?php
namespace Riki\Rma\Controller\Adminhtml\Reason;

class Index extends \Riki\Rma\Controller\Adminhtml\Reason
{
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $result = $this->initPageResult();
        $result->addBreadcrumb(__('Manage Rma Reasons'), __('Manage Rma Reasons'));
        $result->setActiveMenu('Riki_Rma::reason');
        return $result;
    }
}