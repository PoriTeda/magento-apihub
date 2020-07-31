<?php


namespace Riki\Subscription\Controller\Adminhtml\Profile;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Riki\Subscription\Model\ProductCart\ProductCartFactory;
use Riki\Subscription\Model\Profile\ProfileFactory;
use Magento\Customer\Model\CustomerFactory;
use Magento\Catalog\Model\ProductFactory;
use Symfony\Component\Config\Definition\Exception\Exception;
use Riki\Subscription\Model\Constant;
use Magento\Framework\DataObject;

class Add extends Action
{
    /**
     * @var ProductCartFactory
     */
    protected $_productCart;
    /**
     * @var ProfileFactory
     */
    protected $_profile;
    /**
     * @var CustomerFactory
     */
    protected $_customer;
    /**
     * @var ProductFactory
     */
    protected $_product;

    protected $_objectManager;
    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $_session;
    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $_sessionManager;
    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $_profileData;
    /**
     * @var \Riki\Subscription\Helper\Profile\Controller\Add
     */
    protected $_controllerHelper;

    protected $profileCache;

    public function __construct(
        Context $context,
        ProductCartFactory $productCart ,
        ProfileFactory $profileFactory,
        CustomerFactory $customer,
        ProductFactory $productFactory,
        \Magento\Backend\Model\Session $session,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Riki\Subscription\Helper\Profile\Controller\Add $controllerHelper,
        \Magento\Framework\Session\SessionManagerInterface $sessionManager,
        \Riki\Subscription\Helper\Profile\Data $profileData,
        \Riki\Subscription\Model\ProfileCacheRepository $profileCache
    ) {
        $this->_productCart = $productCart;
        $this->_profile = $profileFactory;
        $this->_customer = $customer;
        $this->_product = $productFactory;
        $this->_session = $session;
        $this->_controllerHelper = $controllerHelper;
        $this->_authSession = $authSession;
        $this->_sessionManager = $sessionManager;
        $this->_profileData  = $profileData;
        $this->profileCache = $profileCache;
        parent::__construct($context);
    }

    public function execute()
    {
        return $this->_controllerHelper->execute($this);
    }

    public function getProfileCache()
    {
        $profileId = $this->_request->getParam('id');
        if ($this->_profileData->getTmpProfile($profileId) !== false) {
            $profileId = $this->_profileData->getTmpProfile($profileId)->getData('linked_profile_id');
        }

        return $this->profileCache->getProfileDataCache($profileId);
    }

    /**
     * Save profile data to cache
     *
     * @param $profileCache
     * @throws \Zend_Serializer_Exception
     */
    public function saveToCache($profileCache)
    {
        $this->profileCache->save($profileCache);
    }

    public function getMessageManager()
    {
        return $this->messageManager;
    }

    public function getStrRedirectWhenFailPath()
    {
        return '*/*/edit';
    }

    public function redirect($path, $arguments = [])
    {
        return parent::_redirect($path, $arguments);
    }
}