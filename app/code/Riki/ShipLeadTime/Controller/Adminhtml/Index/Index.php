<?php

namespace Riki\ShipLeadTime\Controller\Adminhtml\Index;

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
        return $this->_authorization->isAllowed('Riki_ShipLeadTime::shipleadtime_view');
    }
    
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(self::ADMIN_RESOURCE);
        $resultPage->addBreadcrumb(__('Shipping Lead Time'), __('Shipping Lead Time'));
        $resultPage->addBreadcrumb(__('Manage Shipping Lead Time'), __('Manage Shipping Lead Time'));
        $resultPage->getConfig()->getTitle()->prepend(__('Shipping Lead Time'));

        return $resultPage;
    }
}