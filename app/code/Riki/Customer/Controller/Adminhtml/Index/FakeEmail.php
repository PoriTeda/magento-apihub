<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Customer\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\Controller\Result\RawFactory;

class FakeEmail extends \Magento\Backend\App\Action
{

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $_customerRepository;
    /**
     * @var \Riki\Sales\Helper\Data
     */
    protected $_orderHelper;
    /**
     * @var \Magento\Framework\Controller\Result\ForwardFactory
     */
    protected $_coreRegistry = null;
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory ;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger ;

    /**
     * FakeEmail constructor.
     * @param Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Riki\Sales\Helper\Data $orderHelper
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Riki\Sales\Helper\Data $orderHelper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_customerRepository = $customerRepository;
        $this->_orderHelper = $orderHelper;
        $this->resultPageFactory  = $resultPageFactory;
        $this->_coreRegistry = $registry;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_logger = $logger;

        parent::__construct($context);
    }

    public function execute()
    {
        $response = array();
        while(true){
            $randomName = $this->makeRandomString(10);
            $randomDomainName = $this->_orderHelper->getOrderRandomDomain();
            $randomDomainName = ($randomDomainName != '')?$randomDomainName:'@example.com';
            $randomEmail = $randomName.$randomDomainName;

            try{
                $customer = $this->_customerRepository->get($randomEmail,$this->_storeManager->getWebsite()->getId());
            }catch (\Exception $e){
                $customer = NULL;
                $this->_logger->info($e->getMessage());
            }

            if(NULL == $customer){
                $response['random_email'] = $randomEmail;
                break;
            }
        }
        return $this->_resultJsonFactory->create()->setData($response);
    }

    /**
     * Make Random String
     *
     * @param int $max
     *
     * @return string
     */
    public function makeRandomString($max=6) {

        return \Zend\Math\Rand::getString($max, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', true);

    }
}
