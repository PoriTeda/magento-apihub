<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Subscription\Controller\Profile;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Reflection\Test\Unit\DataObject;
use Riki\Subscription\Model\Constant;
use Riki\Subscription\Model\SimpleSessionWrapper;
use Symfony\Component\Config\Definition\Exception\Exception;
use Riki\Subscription\Model\Profile\ProfileFactory;
use Riki\Subscription\Model\ProductCart\ProductCartFactory;
use Riki\Subscription\Model\Version\VersionFactory;
use \Riki\Subscription\Model\Profile\Profile;

class Save extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $_formKeyValidator;

    /**
     * @var ProfileFactory
     */
    protected $_profileFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_session;
    /**
     * @var \Riki\Subscription\Helper\Profile\Controller\Save
     */
    protected $controllerHelper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Riki\Subscription\Model\ProfileCacheRepository
     */
    protected $profileCacheRepository;

    /**
     * Save constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param ProfileFactory $profileFactory
     * @param \Riki\Subscription\Helper\Profile\Controller\Save $controllerHelper
     * @param \Magento\Framework\Session\SessionManagerInterface $sessionManager
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Riki\Subscription\Model\ProfileCacheRepository $profileCacheRepository
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        ProfileFactory $profileFactory,
        \Riki\Subscription\Helper\Profile\Controller\Save $controllerHelper,
        \Psr\Log\LoggerInterface $logger,
        \Riki\Subscription\Model\ProfileCacheRepository $profileCacheRepository
    ) {
        $this->_formKeyValidator = $formKeyValidator;
        $this->_profileFactory = $profileFactory;
        $this->controllerHelper = $controllerHelper;
        $this->logger = $logger;
        $this->profileCacheRepository = $profileCacheRepository;
        parent::__construct($context);
    }

    /**
     * @return string
     */
    public function getStrRedirectWhenFail()
    {
        return '*/*/edit';
    }

    /**
     * @return string
     */
    public function getStrRedirectWhenConfirm()
    {
        return $this->_url->getUrl('subscriptions/profile/editSuccess');
    }

    /**
     * @return string
     */
    public function getStrRedirectWhenProfileNotExists()
    {
        return '*/*/edit';
    }

    /**
     * @return \Magento\Framework\Data\Form\FormKey\Validator
     */
    public function getFormkeyValidator()
    {
        return $this->_formKeyValidator;
    }

    /**
     * @return \Magento\Framework\Message\ManagerInterface
     */
    public function getMessageManager()
    {
        return $this->messageManager;
    }

    /**
     * @return \Riki\Subscription\Model\Profile\Profile|null
     */
    public function getProfileCache()
    {
        $profileId = $this->_request->getParam('profile_id');
        if($cacheWrapper = $this->profileCacheRepository->getProfileDataCache($profileId)){
            return $cacheWrapper;
        }
        return null;
    }

    public function saveToCache($profile)
    {
        $this->profileCacheRepository->save($profile);
    }
    /**
     * @param string $path
     * @param array $arguments
     * @return ResponseInterface
     */
    public function _redirect($path, $arguments = [])
    {
        return parent::_redirect($path, $arguments);
    }

    /**
     * Strip tags from received data
     *
     * @param string|array $data
     * @return string|array
     */
    protected function _filterPost($data)
    {
        if (!is_array($data)) {
            return strip_tags($data);
        }
        foreach ($data as &$field) {
            if (!empty($field)) {
                if (!is_array($field)) {
                    $field = strip_tags($field);
                } else {
                    $field = $this->_filterPost($field);
                }
            }
        }
        return $data;
    }

    /**
     * Create gift registry action
     *
     * @return void|ResponseInterface
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function execute()
    {
        return $this->controllerHelper->execute($this);
    }

}
