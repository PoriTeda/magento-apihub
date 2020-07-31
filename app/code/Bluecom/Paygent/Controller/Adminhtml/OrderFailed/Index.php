<?php
namespace Bluecom\Paygent\Controller\Adminhtml\OrderFailed;

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
        return $this->_authorization->isAllowed('Bluecom_Paygent::orderfailed');
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
        $resultPage->setActiveMenu('Bluecom_Paygent::orderfailed');
        $resultPage->addBreadcrumb(__('Recurring Order Failed'), __('Recurring Order Failed'));
        $resultPage->getConfig()->getTitle()->prepend(__('Recurring Order Failed'));

        return $resultPage;
    }
    /**
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->initResultPage();
        $resultPage->getConfig()->getTitle()->prepend(__('Manage Recurring Order Failed'));

        return $resultPage;
    }
}
