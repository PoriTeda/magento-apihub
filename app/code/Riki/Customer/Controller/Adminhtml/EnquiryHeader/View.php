<?php
namespace Riki\Customer\Controller\Adminhtml\EnquiryHeader;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\Controller\ResultFactory;

class View extends Action
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var \Magento\Backend\Model\View\Result\ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * @var \Riki\Customer\Model\EnquiryHeaderFactory
     */
    protected $enquiryHeaderFactory;

    /**
     * View constructor.
     * Header
     * @param Context $context
     * @param Registry $registry
     * @param \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory
     * @param \Riki\Customer\Model\EnquiryHeaderFactory $enquiryHeaderFactory
     */
    public function __construct(
        Context $context,
        Registry $registry,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory,
        \Riki\Customer\Model\EnquiryHeaderFactory $enquiryHeaderFactory
    ) {
        $this->registry = $registry;
        $this->resultForwardFactory = $resultForwardFactory;
        $this->enquiryHeaderFactory = $enquiryHeaderFactory;
        parent::__construct($context);
    }


    /**
     * Enquiry Header detail information page
     *
     * @return \Magento\Backend\Model\View\Result\Forward
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $modelEnquiryHeader= $this->enquiryHeaderFactory->create();

        if ($id) {
            $modelEnquiryHeader->load($id);
            if (!$modelEnquiryHeader->getId()) {
                $this->messageManager->addError(__('This Enquiry Header no longer exists.'));
                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();
                $resultRedirect->setPath('*/*/');

                return $resultRedirect;
            }
        }
        $this->registry->register('enquiryheader_item', $modelEnquiryHeader);

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->addBreadcrumb(__('View Detail Enquiry Header'),__('View Detail Enquiry'));

        $resultPage->getConfig()->getTitle()->prepend(__('View Detail Enquiry'));

        return $resultPage;
    }

}