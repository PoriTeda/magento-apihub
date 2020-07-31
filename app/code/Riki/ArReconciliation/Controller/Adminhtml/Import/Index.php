<?php

namespace Riki\ArReconciliation\Controller\Adminhtml\Import;

class Index extends \Magento\Backend\App\Action
{
    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_ArReconciliation::import_payment_csv');
    }

    /**
     *
     * @return \Magento\Backend\Model\View\Result\Page|\Magento\Backend\Model\View\Result\Redirect
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        $resultPage = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_PAGE);

        $resultPage->setActiveMenu('Riki_ArReconciliation::import_payment_csv_file');

        return $resultPage;
    }
}
