<?php

namespace Riki\TimeSlots\Controller\Adminhtml\Index;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action;

class Index extends  Action
{
    const ADMIN_RESOURCE = 'Riki_SubscriptionFrequency::subscription';
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }
    
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_TimeSlots::manage_time_slots');
    }
    
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(self::ADMIN_RESOURCE);
        $resultPage->addBreadcrumb(__('Time Slots'), __('Time Slots'));
        $resultPage->addBreadcrumb(__('Manage Time Slots'), __('Manage Time Slots'));
        $resultPage->getConfig()->getTitle()->prepend(__('Time Slots'));

        return $resultPage;
    }
}