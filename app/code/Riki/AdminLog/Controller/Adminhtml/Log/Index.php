<?php
namespace Riki\AdminLog\Controller\Adminhtml\Log;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;
use Psr\Log\LoggerInterface;

class Index extends Action
{
    /**
     * @var Registry
     */
    protected $registry;


    protected $modelFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        Action\Context $context,
        Registry $registry,
        LoggerInterface $logger
    )
    {
        $this->registry = $registry;
        $this->logger = $logger;

        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_AdminLog::adminlog');
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function initResultPage()
    {
        /**
         * @var \Magento\Backend\Model\View\Result\Page $resultPage
         */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Riki_AdminLog::adminlog');
        $resultPage->addBreadcrumb(__('Admin Logs'), __('Admin Logs'));
        $resultPage->getConfig()->getTitle()->prepend(__('Admin Logs'));

        return $resultPage;
    }
    /**
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->initResultPage();
        $resultPage->getConfig()->getTitle()->prepend(__('Manage Admin Logs'));
        return $resultPage;
    }
}
