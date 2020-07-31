<?php
namespace Riki\AdminLog\Controller\Adminhtml\Log;

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

    protected $logLogFactory;

    /**
     * View constructor.
     * @param Context $context
     * @param Registry $registry
     * @param \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory
     * @param \Riki\AdminLog\Model\LogFactory $consumerLogFactory
     */
    public function __construct(
        Context $context,
        Registry $registry,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory,
        \Riki\AdminLog\Model\LogFactory $LogFactory
    ) {
        $this->registry = $registry;
        $this->resultForwardFactory = $resultForwardFactory;
        $this->logLogFactory = $LogFactory;
        parent::__construct($context);
    }

    /**
     * Consumer API Log detail information page
     *
     * @return \Magento\Backend\Model\View\Result\Forward
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('log_id');
        $modelLog = $this->logLogFactory->create();
        if ($id) {
            /** @var \Magento\Backend\Model\View\Result\ForwardFactory $modelLog */
            $modelLog->load($id);
            if (!$modelLog->getId()) {
                $this->messageManager->addError(__('This admin log no longer exists.'));
                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();
                $resultRedirect->setPath('*/*/');
                return $resultRedirect;
            }
        }

        $this->registry->register('adminlog_log_item', $modelLog);

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->addBreadcrumb(__('View Detail Admin Log'),__('View Detail Admin Log'));
        $resultPage->getConfig()->getTitle()->prepend(__('View Detail Admin Log'));

        return $resultPage;
    }

}