<?php


namespace Riki\Subscription\Controller\Adminhtml\Profile;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Riki\Subscription\Model\ProductCart\ProductCartFactory;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Config\Definition\Exception\Exception;
use Riki\Subscription\Model\Constant;
class Delete extends Action
{
    /**
     * @var $_productCart
     */
    protected $_productCart;
    protected $_session;
    protected $_sessionManager;

    protected $profileCache;

    public function __construct(
        Context $context,
        ProductCartFactory $productCart,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Riki\Subscription\Helper\Profile\Controller\Delete $controllerHelper,
        \Magento\Framework\Session\SessionManagerInterface $sessionManager,
        \Riki\Subscription\Model\ProfileCacheRepository $profileCache
    ) {
        $this->_productCart         = $productCart;
        $this->_controllerHelper    = $controllerHelper;
        $this->_authSession          = $authSession;
        $this->_sessionManager = $sessionManager;
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

}