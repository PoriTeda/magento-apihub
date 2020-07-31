<?php
namespace Riki\Customer\Controller\Adminhtml\ConsumerDB;

use Magento\Backend\App\Action;

class Detail extends Action
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_dateTime;

    /**
     * @var \Zend\Soap\Client
     */
    protected $_soapClient;

    /**
     * @var \Riki\Customer\Helper\ConsumerLog
     */
    protected $_apiLogger;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;


    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Customer\Api\Data\CustomerInterfaceFactory
     */
    protected $_customerInterface;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $_customerRepository;

    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $_customerModel;

    /**
     * @var \Riki\Sales\Helper\Data
     */
    protected $_orderHelper;

    /**
     * @var \Riki\Customer\Helper\Membership ,
     */
    protected $_customerMembershipHelper;

    /**
     * @var \Riki\Customer\Model\AmbCustomerRepository $ambCustomerRepository
     */
    protected $_ambCustomerRepository;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig ,
     */
    protected $_scopeConfig;

    /**
     * @var \Riki\Customer\Helper\Region
     */
    protected $_regionHelper;

    /**
     * Detail constructor.
     * @param Action\Context $context
     * @param \Zend\Soap\Client $soapClient
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $dateTime
     * @param \Riki\Customer\Helper\ConsumerLog $apiLogger
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerDataFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Riki\Customer\Model\AmbCustomerRepository $ambCustomerRepository ,
     */
    public function __construct(
        Action\Context $context,
        \Zend\Soap\Client $soapClient,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $dateTime,
        \Riki\Customer\Helper\ConsumerLog $apiLogger,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerDataFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\AddressFactory $customerAddressFactory,
        \Magento\Customer\Model\Customer $customerModel,
        \Riki\Sales\Helper\Data $orderHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Riki\Customer\Helper\Membership $membershipHelper,
        \Riki\Customer\Model\AmbCustomerRepository $ambCustomerRepository,
        \Riki\Customer\Helper\Region $regionHelper,
        \Riki\Customer\Model\CustomerRepository $rikiCustomerRepo
    )
    {
        $this->_soapClient = $soapClient;
        $this->_dateTime = $dateTime;
        $this->_apiLogger = $apiLogger;
        $this->_customerFactory = $customerFactory;
        $this->_customerAddressFactory = $customerAddressFactory;
        $this->_storeManager = $storeManager;
        $this->_customerInterface = $customerDataFactory;
        $this->_customerRepository = $customerRepository;
        $this->_customerModel = $customerModel;
        $this->_orderHelper = $orderHelper;
        $this->_customerMembershipHelper = $membershipHelper;
        $this->_ambCustomerRepository = $ambCustomerRepository;
        $this->_scopeConfig = $scopeConfig;
        $this->_regionHelper = $regionHelper;
        $this->_rikiCusRepo = $rikiCustomerRepo;

        parent::__construct($context);
    }

    /**
     * Execute
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        //check customer code in magento
        $consumerDbId = $this->getRequest()->getParam('id');

        if ($consumerDbId) {

            //init action

            $aCustomerCollection = $this->_customerFactory->create()->getCollection();
            $aCustomersDB = $aCustomerCollection->addFieldToFilter('consumer_db_id', $consumerDbId);
            $aCustomerFirstItem = null;
            foreach ($aCustomersDB as $aCustomerDB) {
                $aCustomerFirstItem = $aCustomerDB;
            }

            if (null != $aCustomerFirstItem && $aCustomerFirstItem->getId()) {
                //if yes -> redirect to edit customer
                $customerMagentoId = $aCustomerFirstItem->getId();

                if (strpos($this->_redirect->getRefererUrl(), "sales/order_create") !== false) {

                    $urlRedirect = $this->_redirect->getRefererUrl();
                    if (strpos($urlRedirect, "?customerid=") !== false) {
                        preg_match('/customerid=([^&]+)/', $urlRedirect, $match);
                        if (isset($match[0])) {
                            $newUrlRedirect = preg_replace('/customerid=([^&]+)/', 'customerid=' . $customerMagentoId, $urlRedirect);
                            return $this->_redirect($newUrlRedirect);
                        }
                    } else {
                        return $this->_redirect($this->_redirect->getRefererUrl() . '?customerid=' . $customerMagentoId);
                    }
                } else {

                    return $this->_redirect('customer/index/edit', [
                        'id' => $customerMagentoId
                    ]);
                }
            } else {

                $this->_customerFactory->create()->getResource()->setNeedHandleDuplicateEmailException(true);

                $customerResponse = $this->_rikiCusRepo->prepareAllInfoCustomer($consumerDbId);

                if(isset($customerResponse['customer_api'])) {
                    try{
                        $customerReturn = $this->_rikiCusRepo->createUpdateEcCustomer($customerResponse,$consumerDbId,null,null);
                        if(null != $customerReturn){

                            if (strpos($this->_redirect->getRefererUrl(), "sales/order_create") !== false) {
                                $urlRedirect = $this->_redirect->getRefererUrl();
                                if (strpos($urlRedirect, "?customerid=") !== false) {

                                    preg_match('/customerid=([^&]+)/', $urlRedirect, $match);
                                    if (isset($match[0])) {
                                        $newUrlRedirect = preg_replace('/customerid=([^&]+)/', 'customerid=' . $customerReturn->getId(), $urlRedirect);
                                        return $this->_redirect($newUrlRedirect);
                                    }
                                } else {
                                    return $this->_redirect($urlRedirect . '?customerid=' . $customerReturn->getId());
                                }

                            } else {
                                return $this->_redirect('customer/index/edit', [
                                    'id' => $customerReturn->getId()
                                ]);
                            }
                        }
                        else{
                            return $this->_redirect('customer/index');
                        }
                    }
                    catch(\Exception $e){
                        $this->messageManager->addError(__($e->getMessage()));
                        return $this->_redirect('customer/index');
                    }
                }

            }
        }
    }


    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Customer::consumerdb');
    }

}