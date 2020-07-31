<?php

namespace Riki\SerialCode\Controller\Reward;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;

class Apply extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $_formKeyValidator;
    
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Riki\SerialCode\Model\ResourceModel\SerialCode\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    protected $logger;

    /**
     * Apply constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Riki\SerialCode\Model\ResourceModel\SerialCode\CollectionFactory $collectionFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Riki\SerialCode\Model\ResourceModel\SerialCode\CollectionFactory $collectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_customerSession = $customerSession;
        $this->_formKeyValidator = $formKeyValidator;
        $this->_collectionFactory = $collectionFactory;
        $this->_storeManager = $storeManager;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Apply serial code to issued reward point
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        if (!$this->getRequest()->isAjax()) {
            $backTo = $this->_redirect->getRefererUrl();
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $resultRedirect->setUrl($backTo);
        }
        $response = [];
        try {
            if (!$this->_formKeyValidator->validate($this->getRequest())) {
                throw new LocalizedException(__('Form key is not valid. Please try to refresh!'));
            }
            $customer = $this->_customerSession->getCustomer();
            if (!$customer->getId()) {
                throw new LocalizedException(__('Need to login'));
            }
            $customerCode = $customer->getData('consumer_db_id');
            if (!$customerCode) {
                throw new LocalizedException(__('Customer code is required'));
            }
            /** @var  \Riki\SerialCode\Model\ResourceModel\SerialCode\Collection $collection */
            $collection = $this->_collectionFactory->create();
            $collection->setPageSize(1);
            $collection->getSelect()->where(
                'serial_code = ?', $this->_request->getParam('serial_code')
            );
            if (!$collection->getSize()) {
                throw new LocalizedException(__('This Serial code/Lucky number is invalid.'));
            }
            /** @var \Riki\SerialCode\Model\SerialCode $serialCode */
            $serialCode = $collection->getFirstItem();
            $response = $serialCode->applySerialCode($customer, $this->_storeManager->getStore()->getWebsiteId());
        } catch (\Exception $e) {
            if ($e instanceof LocalizedException) {
                $response['msg'] = $e->getMessage();
            } else {
                $response['msg'] = __('An error occurs.');
                $this->logger->critical($e);
            }

            $response['err'] = true;

        }

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        return $resultJson->setData($response);
    }
}
