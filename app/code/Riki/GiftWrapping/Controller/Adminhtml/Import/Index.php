<?php

namespace Riki\GiftWrapping\Controller\Adminhtml\Import;

use Magento\Framework\Controller\ResultFactory;

class Index extends \Magento\Backend\App\Action
{
    public function __construct(
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory,
        \Magento\Backend\App\Action\Context $context
    ) {
        parent::__construct($context);
        $this->resultForwardFactory = $resultForwardFactory;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_GiftWrapping::import_giftwrapping');
    }

    public function execute()
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Riki_GiftWrapping::import_giftwrapping');
        $resultPage->addBreadcrumb(__('Import CSV GiftWrapping'), __('Import CSV GiftWrapping'));
        $resultPage->getConfig()->getTitle()->prepend(__('Import CSV GiftWrapping'));
        return $resultPage;
    }
}