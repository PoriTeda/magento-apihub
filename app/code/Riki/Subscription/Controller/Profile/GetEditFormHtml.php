<?php

namespace Riki\Subscription\Controller\Profile;

use Magento\Framework\DataObject;
use Riki\Subscription\Block\Frontend\Profile\Edit as ProfileEditBlock;
use Riki\Subscription\Helper\Data as SubscriptionHelperData;

class GetEditFormHtml extends \Magento\Framework\App\Action\Action
{
    const EDIT_REGISTRATION_SHIPPING_ADDRESS_ID = 'edit_registration_shipping_address_id';
    /* @var \Magento\Customer\Api\AddressRepositoryInterface */
    protected $_customerAddressRepository;

    /* @var SubscriptionHelperData */
    protected $subscriptionHelperData;

    /* @var \Magento\Framework\Registry */
    protected $_registry;

    /**
     * @var \Magento\Framework\View\LayoutFactory
     */
    protected $layoutFactory;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJson;

    /* @var \Magento\Framework\Session\SessionManager */
    protected $_sessionManager;

    protected $helperProfileData;

    public function __construct(
        \Magento\Framework\Session\SessionManager $sessionManager,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Framework\Registry $registry,
        SubscriptionHelperData $subscriptionHelperData,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepositoryInterface,
        \Magento\Framework\App\Action\Context $context,
        \Riki\Subscription\Helper\Profile\Data $helperProfileData
    ){
        $this->_sessionManager = $sessionManager;
        $this->_resultJson = $jsonFactory;
        $this->layoutFactory = $layoutFactory;
        $this->_registry = $registry;
        $this->subscriptionHelperData = $subscriptionHelperData;
        $this->_customerAddressRepository = $addressRepositoryInterface;
        $this->helperProfileData = $helperProfileData;
        parent::__construct($context);
    }

    public function execute()
    {

        $this->_request = $this->getRequest();
        $params = $this->_request->getParams();
        $currentShippingAddressId = $params['currentShippingAddressId'];
        $response = ['type_id' => ProfileEditBlock::ADDRESS_TYPE_ANOTHER, 'link_edit' => '', 'html_edit_form' => ''];
        $typeId = $this->checkAddressType($currentShippingAddressId);
        $response['type_id'] = $typeId;
        if ($typeId == ProfileEditBlock::ADDRESS_TYPE_HOME_NO_COMPANY) {
            $response['link_edit'] = 1;
        } elseif ($typeId == ProfileEditBlock::ADDRESS_TYPE_HOME_HAVE_COMPANY) {
            $response['link_edit'] = 2;
        } elseif ($typeId == ProfileEditBlock::ADDRESS_TYPE_AMBASSADOR_COMPANY) {
            $response['link_edit'] = 3;
        } else {
            if(!is_null($this->_registry->registry(self::EDIT_REGISTRATION_SHIPPING_ADDRESS_ID))){
                $this->_registry->unregister(self::EDIT_REGISTRATION_SHIPPING_ADDRESS_ID);
            }
            $layout = $this->layoutFactory->create();
            $this->_registry->register(self::EDIT_REGISTRATION_SHIPPING_ADDRESS_ID, $currentShippingAddressId);
            $block = $layout->createBlock('Riki\Subscription\Block\Frontend\Profile\Address\Change');
            $block->setArea(\Magento\Framework\App\Area::AREA_FRONTEND);
            $htmlEditAddressForm = $block->setTemplate('Riki_Subscription::customer/address/edit.phtml')->toHtml();
            $response['html_edit_form'] = $htmlEditAddressForm;
        }
        $sessionWrapper = $this->getSession($params['profileId']);
        if($sessionWrapper instanceof \Magento\Framework\DataObject) {
            $sessionWrapper->setData('new_shipping_address_id', $currentShippingAddressId);
        }else{
            $sessionWrapper = $this->renewSessionProfile($params['profileId']);
            $sessionWrapper->setData('new_shipping_address_id', $currentShippingAddressId);
        }
        $resultJson = $this->_resultJson->create();
        return $resultJson->setData($response);
    }

    /**
     * Get Profile Session
     *
     * @return bool
     */
    public function getSession($profileId)
    {
        if($sessionWrapper = $this->_sessionManager->getProfileData()){
            if(isset($sessionWrapper[$profileId])) {
                return $sessionWrapper[$profileId];
            }
        }
        return false;
    }
    /**
     * Check address type
     *
     * @param $shippingAddressId
     * @return int (1 | home address, 2 | Company | 3 another)
     */

    public function checkAddressType($shippingAddressId)
    {
        try {
            $customerAddress = $this->_customerAddressRepository->getById($shippingAddressId);
            if ($customerAddress->getCustomAttribute('riki_type_address')->getValue() == 'home'
                && $customerAddress->getCompany() == null
            ) {
                return ProfileEditBlock::ADDRESS_TYPE_HOME_NO_COMPANY;
            } elseif ($customerAddress->getCustomAttribute('riki_type_address')->getValue() == 'home'
                && $customerAddress->getCompany() != null
            ) {
                return ProfileEditBlock::ADDRESS_TYPE_HOME_HAVE_COMPANY;
            } elseif ($customerAddress->getCustomAttribute('riki_type_address')->getValue() == 'company') {
                return ProfileEditBlock::ADDRESS_TYPE_AMBASSADOR_COMPANY;
            } else {
                return ProfileEditBlock::ADDRESS_TYPE_ANOTHER;
            }
        } catch (\Exception $e) {
            return ProfileEditBlock::ADDRESS_TYPE_ANOTHER;
        }
    }
    public function renewSessionProfile($profileId){
        /** @var \Riki\Subscription\Model\Profile\Profile $objProfile */
        $objProfile  = $this->helperProfileData->load($profileId);
        $objNew = new DataObject();
        $objNew->setData($objProfile->getData());
        $objNew->setData("course_data", $objProfile->getCourseData());
        $objNew->setData("product_cart", $objProfile->getProductCartData());
        $profileData[$profileId] = $objNew;
        $this->_sessionManager->setProfileData($profileData);
        return $this->_sessionManager->getProfileData()[$profileId];
    }
}