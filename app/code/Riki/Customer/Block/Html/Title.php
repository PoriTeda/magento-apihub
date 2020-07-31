<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Customer\Block\Html;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;

/**
 * Html page title block
 *
 * @method $this setTitleId($titleId)
 * @method $this setTitleClass($titleClass)
 * @method string getTitleId()
 * @method string getTitleClass()
 */
class Title extends \Magento\Framework\View\Element\Template
{
    /**
     * Own page title to display on the page
     *
     * @var string
     */
    protected $pageTitle;

    /**
     * @var \Magento\Newsletter\Model\SubscriberFactory
     */
    protected $_subscriberFactory;

    /** @var \Magento\Customer\Helper\View */
    protected $_helperView;

    /**
     * Address helper
     *
     * @var \Magento\Customer\Helper\Address
     */
    protected $_addressHelper;

    /**
     * @var \Magento\Customer\Model\Address\Mapper
     */
    protected $addressMapper;

    protected $_addressRepositoryInterface;

    /**
     * @var \Magento\Customer\Helper\Session\CurrentCustomer
     */
    protected $currentCustomer;
    protected $_httpRequest;
    /**
     * @var \Magento\Store\Api\WebsiteRepositoryInterface
     */
    protected $_websiteRepositoryInterface;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    protected $_coreRegistry;
    /**
     * @var \Riki\CedynaInvoice\Helper\Data
     */
    protected $cedynaDataHelper;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * Title constructor.
     * @param Template\Context $context
     * @param \Magento\Framework\App\Request\Http $httpRequest
     * @param \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     * @param \Magento\Customer\Helper\View $helperView
     * @param \Magento\Customer\Helper\Address $addressHelper
     * @param \Magento\Customer\Model\Address\Mapper $addressMapper
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepositoryInterface
     * @param \Magento\Store\Api\WebsiteRepositoryInterface $websiteRepositoryInterface
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\CedynaInvoice\Helper\Data $cedynaDataHelper
     * @param \Magento\Customer\Model\Session $customerSession
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\Request\Http $httpRequest,
        \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        \Magento\Customer\Helper\View $helperView,
        \Magento\Customer\Helper\Address $addressHelper,
        \Magento\Customer\Model\Address\Mapper $addressMapper,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepositoryInterface,
        \Magento\Store\Api\WebsiteRepositoryInterface $websiteRepositoryInterface,
        \Magento\Framework\Registry $registry,
        \Riki\CedynaInvoice\Helper\Data $cedynaDataHelper,
        \Magento\Customer\Model\Session $customerSession,
        array $data = []
    ) {
        $this->currentCustomer = $currentCustomer;
        $this->_subscriberFactory = $subscriberFactory;
        $this->_helperView = $helperView;
        $this->_httpRequest = $httpRequest;
        $this->_addressHelper = $addressHelper;
        $this->addressMapper = $addressMapper;
        $this->_addressRepositoryInterface = $addressRepositoryInterface;
        $this->_websiteRepositoryInterface = $websiteRepositoryInterface;
        $this->scopeConfig = $context->getScopeConfig();
        $this->_coreRegistry = $registry;
        $this->cedynaDataHelper = $cedynaDataHelper;
        $this->customerSession = $customerSession;
        parent::__construct($context, $data);
    }
    public function getCustomer()
    {
        try {
            return $this->currentCustomer->getCustomer();
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return null;
        }
    }

    public function getAction(){
        return $this->_httpRequest->getFullActionName();
    }
    /**
     * Get the full name of a customer
     *
     * @return string full name
     */
    public function getName()
    {
        if($this->getCustomer() == null){
            return '';
        }else{
            return $this->_helperView->getCustomerName($this->getCustomer());
        }

    }
    /**
     * Provide own page title or pick it from Head Block
     *
     * @return string
     */
    public function getPageTitle()
    {
        if (!empty($this->pageTitle)) {
            return $this->pageTitle;
        }
        return $this->pageConfig->getTitle()->getShort();
    }

    /**
     * Provide own page content heading
     *
     * @return string
     */
    public function getPageHeading()
    {
        if (!empty($this->pageTitle)) {
            return $this->pageTitle;
        }
        return $this->pageConfig->getTitle()->getShortHeading();
    }

    /**
     * Set own page title
     *
     * @param string $pageTitle
     * @return void
     */
    public function setPageTitle($pageTitle)
    {
        $this->pageTitle = $pageTitle;
    }

    /**
     * @return bool|mixed
     */
    public function checkMember(){
        if($this->getCustomer()){
            $memberShip = $this->getCustomer()->getCustomAttribute('membership');
            if($memberShip){
                return $memberShip->getValue();
            }
        }
        return false;
    }

    /**
     * @return string
     */
    public function getTitleForShipmentHistoryPage(){

        $addressString = '';

        $name = '';

        $addressId = $this->_coreRegistry->registry('address_id');

        if(!$addressId)
            $addressId = $this->getCustomer()->getDefaultShipping();

        try{
            $address = $this->_addressRepositoryInterface->getById($addressId);
        }catch (\Exception $e){
            $address = null;
        }

        if($address){

            $apartment = null;

            if($address->getCustomAttribute('apartment') != NULL){
                $apartment =  ' ' . $address->getCustomAttribute('apartment')->getValue();
            }else{
                $apartment = '';
            }

            $name .= $address->getLastname() . $address->getFirstname();

            $addressString .= '〒' . $address->getPostcode() . ' ' .
                $address->getRegion()->getRegion() . ' ' .
                implode(', ', $address->getStreet()) .
                $apartment
                ;
        }

        return __('Delivery history (Address: %1Mr %2)', $name, $this->escapeHtml($addressString));
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getTitle(){

        if($this->getAction() == 'sales_shipment_history')
            return $this->getTitleForShipmentHistoryPage();

        return __('My page of %1', $this->getName());
    }

    /**
     * @return mixed
     */
    public function getWebsiteList(){
        try {
            $websiteId = $this->_storeManager->getStore()->getWebsiteId();
            $website = $this->_websiteRepositoryInterface->getById($websiteId);
            if ($website->getCode() == \Riki\Customer\Block\Account\Info::MEMBERSHIP_CIS_CODE || $website->getCode() == \Riki\Customer\Block\Account\Info::MEMBERSHIP_CNC_CODE) {
                return true;
            } else {
                return false;
            }
        } catch (NoSuchEntityException $e) {
            return false;
        }
    }
    /**
     * Get System Config
     *
     * @param $path
     *
     * @return mixed
     */
    public function getSystemConfig($path)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $config = $this->scopeConfig->getValue($path, $storeScope);
        return $config;
    }

    /**
     * Check customer is shosha
     * @return bool
     */
    public function isCedynaCustomer()
    {
        return $this->cedynaDataHelper->canCedynaInvoice($this->getCustomer());
    }
}
