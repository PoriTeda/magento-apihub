<?php

namespace Riki\Loyalty\Controller\Adminhtml;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Customer\Model\Address\Mapper;
use Magento\Framework\Message\Error;
use Magento\Framework\DataObjectFactory as ObjectFactory;
use Magento\Framework\Api\DataObjectHelper;


abstract class Reward extends \Magento\Backend\App\Action
{

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Framework\View\Result\LayoutFactory
     */
    protected $_resultLayoutFactory;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * @var \Riki\Loyalty\Model\ConsumerDb\ShoppingPoint
     */
    protected $_consumerDb;

    /**
     * @var \Riki\Loyalty\Helper\Data
     */
    protected $_loyaltyHelper;

    /**
     * @var \Riki\Loyalty\Model\RewardFactory
     */
    protected $_rewardFactory;

    /**
     * @var \Riki\Loyalty\Model\ResourceModel\RewardFactory
     */
    protected $_rewardResourceFactory;

    /**
     * @var \Magento\Customer\Model\ResourceModel\CustomerRepository
     */
    protected $_customerRepository;

    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    protected $_orderRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Admin reward point base controller constructor
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Riki\Loyalty\Model\RewardFactory $rewardFactory
     * @param \Riki\Loyalty\Model\ResourceModel\RewardFactory $rewardResourceFactory
     * @param \Riki\Loyalty\Model\ConsumerDb\ShoppingPoint $consumerDb
     * @param \Riki\Loyalty\Helper\Data $helper
     * @param \Magento\Customer\Model\ResourceModel\CustomerRepository $customerRepository
     * @param \Magento\Sales\Model\OrderRepository $orderRepository
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Riki\Loyalty\Model\RewardFactory $rewardFactory,
        \Riki\Loyalty\Model\ResourceModel\RewardFactory $rewardResourceFactory,
        \Riki\Loyalty\Model\ConsumerDb\ShoppingPoint $consumerDb,
        \Riki\Loyalty\Helper\Data $helper,
        \Magento\Customer\Model\ResourceModel\CustomerRepository $customerRepository,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_scopeConfig = $scopeConfig;
        $this->_resultLayoutFactory = $resultLayoutFactory;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_consumerDb = $consumerDb;
        $this->_loyaltyHelper = $helper;
        $this->_rewardFactory = $rewardFactory;
        $this->_rewardResourceFactory = $rewardResourceFactory;
        $this->_customerRepository = $customerRepository;
        $this->_orderRepository = $orderRepository;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Customer initialization
     *
     * @return string customer id
     */
    protected function initCurrentCustomer()
    {
        $customerId = (int)$this->getRequest()->getParam('id');

        if ($customerId) {
            $customer = $this->_customerRepository->getById($customerId);
            $attribute = $customer->getCustomAttribute('consumer_db_id');
            if ($attribute) {
                $this->_coreRegistry->register('current_customer_code', $attribute->getValue());
            } else {
                $this->_coreRegistry->register('current_customer_code', $customerId);
            }
        }

        return $customerId;
    }

    /**
     * Prepare customer default title
     *
     * @param \Magento\Backend\Model\View\Result\Page $resultPage
     * @return void
     */
    protected function prepareDefaultCustomerTitle(\Magento\Backend\Model\View\Result\Page $resultPage)
    {
        $resultPage->getConfig()->getTitle()->prepend(__('Rewards'));
    }

    /**
     * Customer access rights checking
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_Customer::manage');
    }
}
