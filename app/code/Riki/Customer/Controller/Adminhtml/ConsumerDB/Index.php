<?php
namespace Riki\Customer\Controller\Adminhtml\ConsumerDB;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Registry;
use Psr\Log\LoggerInterface;

class Index extends Action
{
    /**
     * @var CONST_DEFAULT_COUNTRY
     */
    const CONST_DEFAULT_COUNTRY = 'JP';

    /**
     * @var ADMIN_RESOURCE
     */
    const ADMIN_RESOURCE = 'Riki_Customer::consumerdb';

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
    protected $_logger;

    public function __construct(
        Action\Context $context,
        Registry $registry,
        LoggerInterface $logger,
        \Zend\Soap\Client $soapclient,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Riki\Customer\Model\ConsumerLog $consumerLog
    )
    {
        $this->registry = $registry;

        $this->_logger = $logger;

        $this->_soapclient = $soapclient;

        $this->_dateTime = $dateTime;

        $this->_consumerLog = $consumerLog;

        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Customer::consumerdb');
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
        $resultPage->setActiveMenu('Riki_Customer::consumerdb');
        $resultPage->addBreadcrumb(__('Browse customers from ConsumerDB'), __('Browse customers from ConsumerDB'));
        $resultPage->getConfig()->getTitle()->prepend(__('Browse customers from ConsumerDB'));

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
        return $resultPage;
    }

}
