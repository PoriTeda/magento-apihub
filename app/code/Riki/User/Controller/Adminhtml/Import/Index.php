<?php

namespace Riki\User\Controller\Adminhtml\Import;

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
    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_User::import_password');
    }

    public function execute()
    {
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Riki_User::import_password');
        $resultPage->addBreadcrumb(__('Import Password Dictionary '), __('Import Password Dictionary '));
        $resultPage->getConfig()->getTitle()->prepend(__('Import Password Dictionary '));

        return $resultPage;
    }
}
