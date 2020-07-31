<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Smtp
 */


namespace Amasty\Smtp\Controller\Adminhtml\Log;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

class View extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @param Context     $context
     * @param PageFactory $resultPageFactory
     * @param Registry    $registry
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Registry $registry
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->registry = $registry;
    }

    /**
     * Check the permission to run it
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Amasty_Smtp::reports_sent');
    }

    /**
     * Index action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $logMessageId = (int) $this->getRequest()->getParam('id');
        $logMessage = $this->_objectManager->create('Amasty\Smtp\Model\Log');
        $logMessage->load($logMessageId);

        if ($logMessage && !$logMessage->getId()) {
            $this->messageManager->addError(__('This log message no longer exists.'));
            /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('*/*/');
        }

        $this->registry->register('current_log_message', $logMessage);

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->addHandle('amsmtp_log_message');
        $resultPage->setActiveMenu('Amasty_Smtp::reports_sent');
        $resultPage->getConfig()->getTitle()->prepend(__('Sent Emails Log'));
        $resultPage->getConfig()->getTitle()->prepend(__('View Message'));

        return $resultPage;
    }
}
