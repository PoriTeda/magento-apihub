<?php
namespace Riki\CedynaInvoice\Controller\Adminhtml\Invoice;

/**
 * Class Index
 * @package Riki\CedynaInvoice\Controller\Adminhtml\Invoice
 */
class Index extends AbstractInvoice
{

    /**
     * Implement Index action
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->initResultPage();
        $resultPage->getConfig()->getTitle()->prepend(__('Cedyna Invoice management'));
        return $resultPage;
    }
}
