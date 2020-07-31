<?php

namespace Riki\Customer\Controller\Account;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\ResponseInterface;

class ValidateSession extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\PageCache\Model\Config
     */
    protected $pageCacheConfig;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Riki\Customer\Model\CheckSessionKSS
     */
    protected $ssoSession;

    /**
     * @var \Magento\Framework\Stdlib\CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var \Magento\Framework\Url\EncoderInterface
     */
    protected $urlEncoder;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * ValidateSession constructor.
     *
     * @param Context $context
     * @param \Magento\PageCache\Model\Config $pageCacheConfig
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Riki\Customer\Model\CheckSessionKSS $ssoSession
     * @param \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager
     * @param \Magento\Framework\Url\EncoderInterface $urlEncoder
     */
    public function __construct(
        Context $context,
        \Magento\PageCache\Model\Config $pageCacheConfig,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Customer\Model\Session $customerSession,
        \Riki\Customer\Model\CheckSessionKSS $ssoSession,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Url\EncoderInterface $urlEncoder
    )
    {
        parent::__construct($context);

        $this->pageCacheConfig = $pageCacheConfig;
        $this->scopeConfig = $scopeConfig;
        $this->customerSession = $customerSession;
        $this->ssoSession = $ssoSession;
        $this->cookieManager = $cookieManager;
        $this->urlEncoder = $urlEncoder;
    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     */
    public function execute()
    {
        $result = [];

        if ($this->pageCacheConfig->getType() == \Magento\PageCache\Model\Config::VARNISH
            && $this->pageCacheConfig->isEnabled()
            && $this->scopeConfig->getValue('sso_login_setting/sso_group/use_sso_login', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
        ) {
            $ssoCookie = $this->cookieManager->getCookie(\Riki\Customer\Model\SsoConfig::SSO_SESSION_ID);
            $ssoSession = $this->ssoSession->checkSession($ssoCookie);
            $ssoConsumerDbId = isset($ssoSession['consumerDbId']) ? $ssoSession['consumerDbId'] : null;
            $customer = $this->customerSession->getCustomer();
            $currentUrl = $this->getRequest()->getParam('current_url');

            $loginUrl = $this->_url->getUrl('customer/account/ssologin', [
                ActionInterface::PARAM_NAME_URL_ENCODED => $this->urlEncoder->encode($currentUrl)
            ]);

            $isSessionValid  = true;
            $cleanStorage = true;
            $redirectUrl = null;
            if ($this->customerSession->isLoggedIn()) {
                if (!$ssoConsumerDbId) {
                    $isSessionValid = false;
                    $redirectUrl = $this->_url->getUrl('customer/account/logout');
                } elseif ($customer->getData('consumer_db_id') != $ssoConsumerDbId) {
                    $isSessionValid = false;
                    $redirectUrl = $loginUrl;
                }
            } else {
                if ($ssoConsumerDbId) {
                    $isSessionValid = false;
                    $redirectUrl = $loginUrl;
                } elseif ($this->getRequest()->getParam('has_data')) {
                    $isSessionValid = false;
                    $redirectUrl = $this->_url->getUrl('customer/account/refreshcookie', [
                        ActionInterface::PARAM_NAME_URL_ENCODED => $this->urlEncoder->encode(
                            $this->getRequest()->getParam('current_url')
                        )
                    ]);
                }
            }

            $result['status'] = $isSessionValid;
            $result['cleanStorage'] = $cleanStorage;
            $result['redirectUrl'] = $redirectUrl;

            $eventData = new \Magento\Framework\DataObject($result);
            $this->_eventManager->dispatch('riki_customer_validate_session', [
                'result' => $eventData,
                'current_url' => $currentUrl
            ]);

            $result = $eventData->getData();
        }

        return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON)
            ->setData($result);
    }
}
