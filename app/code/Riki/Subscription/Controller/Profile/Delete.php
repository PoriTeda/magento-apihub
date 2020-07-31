<?php


namespace Riki\Subscription\Controller\Profile;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Riki\Subscription\Model\ProductCart\ProductCartFactory;
use Riki\Subscription\Model\Constant;
class Delete extends Action
{
    /**
     * @var $_productCart
     */
    protected $_productCart;
    /**
     * @var \Riki\Subscription\Helper\Profile\Controller\Delete
     */
    protected $_controllerHelper;

    /* @var \Riki\Subscription\Helper\Profile\Data */
    protected $_profileData;

    protected $profileCacheRepository;

    public function __construct(
        \Riki\Subscription\Helper\Profile\Data $subHelperProfile,
        Context $context,
        ProductCartFactory $productCart,
        \Magento\Customer\Model\Session $custSession,
        \Riki\Subscription\Helper\Profile\Controller\Delete $controllerHelper,
        \Riki\Subscription\Model\ProfileCacheRepository $profileCacheRepository
    ) {
        $this->_profileData = $subHelperProfile;
        $this->_productCart = $productCart;
        $this->_controllerHelper = $controllerHelper;
        $this->profileCacheRepository = $profileCacheRepository;
        parent::__construct($context);
    }

    public function execute()
    {
        return $this->_controllerHelper->execute($this);
    }

    /**
     * @return \Riki\Subscription\Model\Profile\Profile|bool
     */
    public function getProfileCache()
    {
        $profileId = $this->_request->getParam('id');
        return $this->profileCacheRepository->getProfileDataCache($profileId);
    }

    /**
     * @param $profileCache
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function saveToCache($profileCache)
    {
        $this->profileCacheRepository->save($profileCache);
    }
}