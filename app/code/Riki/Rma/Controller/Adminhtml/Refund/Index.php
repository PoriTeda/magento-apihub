<?php
namespace Riki\Rma\Controller\Adminhtml\Refund;

class Index extends \Riki\Rma\Controller\Adminhtml\Refund
{
    const ADMIN_RESOURCE = 'Riki_Rma::rma_refund_actions_view';

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        return $this->initPageResult();
    }

    /**
     * {@inheritdoc}
     */
    public function initPageResult()
    {
        $result = parent::initPageResult();

        $result->addBreadcrumb(__('Management'), __('Management'));
        $result->getConfig()->getTitle()->prepend(__('Management'));

        return $result;
    }
}