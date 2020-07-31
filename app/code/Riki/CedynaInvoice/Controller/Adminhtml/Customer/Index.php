<?php
namespace Riki\CedynaInvoice\Controller\Adminhtml\Customer;

/**
 * Class Index
 * @package Riki\CedynaInvoice\Controller\Adminhtml\Customer
 */
class Index extends AbstractCustomer
{

    /**
     * Implement Index action
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_PAGE);
        $resultPage->addContent(
            $resultPage->getLayout()->createBlock('Riki\CedynaInvoice\Block\Adminhtml\Customer')
            ->setData('customerId', $this->_request->getParam('id'))
            ->setData('target', $this->_request->getParam('target'))
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Customer cedyna invoices'));
        return $resultPage;
    }
}
