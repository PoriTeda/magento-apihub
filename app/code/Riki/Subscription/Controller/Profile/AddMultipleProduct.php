<?php


namespace Riki\Subscription\Controller\Profile;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Riki\Subscription\Model\ProductCart\ProductCartFactory;
use Riki\Subscription\Model\Profile\ProfileFactory;
use Magento\Customer\Model\CustomerFactory;
use Magento\Catalog\Model\ProductFactory;
use Symfony\Component\Config\Definition\Exception\Exception;
use Riki\Subscription\Model\Constant;
use Magento\Framework\DataObject;

class AddMultipleProduct extends Action
{
    /**
     * @var $_productCart
     */
    protected $_productCart;

    protected $_profile;

    protected $_customer;

    protected $_product;

    protected $_objectManager;
    protected $_profileData;

    protected $profileCacheRepository;
    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $_sessionManager;

    public function __construct(
        Context $context,
        ProductCartFactory $productCart ,
        ProfileFactory $profileFactory,
        CustomerFactory $customer,
        ProductFactory $productFactory,
        \Magento\Customer\Model\Session $session,
        \Riki\Subscription\Helper\Profile\Data $profileData,
        \Riki\Subscription\Helper\Profile\Controller\AddMultipleProduct $controllerHelper,
        \Riki\Subscription\Model\ProfileCacheRepository $profileCacheRepository
    )
    {
        $this->_profileData = $profileData;
        $this->_productCart = $productCart;
        $this->_profile = $profileFactory;
        $this->_customer = $customer;
        $this->_product = $productFactory;
        $this->customerSession = $session;
        $this->_controllerHelper = $controllerHelper;
        $this->profileCacheRepository = $profileCacheRepository;

        parent::__construct($context);
    }

    public function execute()
    {
        return $this->_controllerHelper->execute($this);
    }

    /**
     * @param $profileCache
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function saveToCache($profileCache)
    {
        $this->profileCacheRepository->save($profileCache);
    }

    /**
     * @return bool|mixed
     */
    public function getProfileCache()
    {
        $profileId = $this->_request->getParam('id');
        if ($this->_profileData->getTmpProfile($profileId) !== false) {
            $profileId = $this->_profileData->getTmpProfile($profileId)->getData('linked_profile_id');
        }
        return $this->profileCacheRepository->getProfileDataCache($profileId);
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