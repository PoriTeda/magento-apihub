<?php
namespace Riki\Customer\Controller\Adminhtml\Shosha;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;
use Psr\Log\LoggerInterface;

class Index extends Action
{
    const ADMIN_RESOURCE = 'Riki_Customer::shoshacustomer';

    /**
     * @var Registry
     */
    protected $registry;


    /**
     * @var ModelFactory
     */
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
        return $this->_authorization->isAllowed('Riki_Customer::shoshacustomer');
    }

    /**
     * InitResultPage
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function initResultPage()
    {
        /**
         * @var \Magento\Backend\Model\View\Result\Page $resultPage
         */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Riki_Customer::shoshacustomer');
        $resultPage->addBreadcrumb(__('Manage Shosha'), __('Manage Shosha'));
        $resultPage->getConfig()->getTitle()->prepend(__('Manage Shosha'));

        return $resultPage;
    }
    /**
     * Execute
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->initResultPage();
        $resultPage->getConfig()->getTitle()->prepend(__('Manage Shosha'));

        return $resultPage;
    }
}
