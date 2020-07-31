<?php

namespace Riki\Customer\Plugin\FrontController;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Response\Http as ResponseHttp;
use Magento\Integration\Model\Oauth\TokenFactory as TokenModelFactory;

class CheckAuthentication
{
    const CUSTOMER_ACCESS_TOKEN = 'customer_access_token';
    const DOMAIN_COOKIE = "nestle.jp";

    /**
     * @var \Riki\Customer\Model\SsoConfig
     */
    protected $ssoConfig;

    /**
     * @var \Riki\Customer\Model\CheckSessionKSS
     */
    protected $ssoSessionChecker;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var \Magento\Framework\Url\Helper\Data
     */
    protected $urlHelper;

    /**
     * @var ResponseHttp
     */
    protected $response;

    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var \Magento\Framework\Stdlib\CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    protected $cookieMetadataFactory;

    /**
     * Token Model
     *
     * @var TokenModelFactory
     */
    private $tokenModelFactory;

    /**
     * CheckAuthentication constructor.
     * @param \Riki\Customer\Model\SsoConfig $ssoConfig
     * @param \Riki\Customer\Model\CheckSessionKSS $checkSessionKSS
     * @param \Magento\Customer\Model\Session $session
     * @param \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory
     * @param \Magento\Framework\Url\Helper\Data $urlHelper
     * @param ResponseHttp $response
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager
     * @param \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory
     * @param \Magento\Integration\Model\CustomerTokenService $customerTokenService
     * @param TokenModelFactory $tokenModelFactory
     */

    public function __construct(
        \Riki\Customer\Model\SsoConfig $ssoConfig,
        \Riki\Customer\Model\CheckSessionKSS $checkSessionKSS,
        \Magento\Customer\Model\Session $session,
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        \Magento\Framework\App\Response\Http $response,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
        \Magento\Integration\Model\CustomerTokenService $customerTokenService,
        TokenModelFactory $tokenModelFactory
    )
    {
        $this->ssoConfig = $ssoConfig;
        $this->ssoSessionChecker = $checkSessionKSS;
        $this->customerSession = $session;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->urlHelper = $urlHelper;
        $this->response = $response;
        $this->resultRawFactory = $resultRawFactory;
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->tokenModelFactory = $tokenModelFactory;
    }

    /**
     * Run check SSO cookie before cache plugin.
     *
     * @param \Magento\Framework\App\FrontControllerInterface $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\App\RequestInterface $request
     *
     * @return \Magento\Framework\Controller\ResultInterface
     * @throws \Exception
     */
    public function aroundDispatch(
        \Magento\Framework\App\FrontControllerInterface $subject,
        \Closure $proceed,
        \Magento\Framework\App\RequestInterface $request
    )
    {
        $isLoggedIn = $this->customerSession->isLoggedIn();
        $customerData = $this->customerSession->getCustomer();

        if ($this->_shouldSkipChecking($request)
            || (!$isLoggedIn && $this->customerSession->getHasMissingInformation()) // allow to access as guest until checkout
        ) {
            return $proceed($request);
        }

        $resultRedirect = null;
        $ssoConsumerDbId = null;
        if ($this->ssoConfig->isEnabled()) {
            if ($ssoSid = $request->getCookie(\Riki\Customer\Model\SsoConfig::SSO_SESSION_ID, null)) {
                $checkSession = $this->ssoSessionChecker->checkSession($ssoSid);
                $ssoConsumerDbId = isset($checkSession['consumerDbId']) ? $checkSession['consumerDbId'] : null;

                if ($request->isAjax() && $isLoggedIn && !$ssoConsumerDbId) {
                    $this->deleteCookieToken();
                    $this->response->setNoCacheHeaders();
                    return $this->resultRawFactory->create()
                        ->setHttpResponseCode(401);
                } else if ($isLoggedIn && !$ssoConsumerDbId) {
                    $this->deleteCookieToken();
                    $resultRedirect = $this->resultRedirectFactory->create()
                        ->setPath('customer/account/logout');
                } else if (!$isLoggedIn && $ssoConsumerDbId) {
                    $this->deleteCookieToken();
                    $resultRedirect = $this->resultRedirectFactory->create()
                        ->setPath('customer/account/ssologin', [
                            ActionInterface::PARAM_NAME_URL_ENCODED => $this->urlHelper->getCurrentBase64Url()
                        ]);
                } else if ($isLoggedIn && $this->customerSession->getCustomer()->getData('consumer_db_id') != $ssoConsumerDbId) { // fix bug NED-7628
                    $this->deleteCookieToken();
                    $resultRedirect = $this->resultRedirectFactory->create()
                        ->setPath('customer/account/logout');
                }
            } elseif ($isLoggedIn) {
                $this->deleteCookieToken();
                $resultRedirect = $this->resultRedirectFactory->create()
                    ->setPath('customer/account/logout');
            }

            /**
             * Special case: PHPSESSID and SSOSID are both expired.
             * This case cannot happen with current setup since PHPSESSID is set as session cookie.
             * Thus PHPSESSID will never be expired.
             */
        }

        if ($isLoggedIn && $ssoConsumerDbId && $this->ssoConfig->isEnabledApp()) {
            $this->setCookieCustomerToken($customerData);
        }

        if ($resultRedirect) {
            $this->response->setNoCacheHeaders();
            return $resultRedirect;
        }

        return $proceed($request);
    }

    /**
     * @param \Magento\Framework\App\RequestInterface $request
     *
     * @return bool
     */
    protected function _shouldSkipChecking($request)
    {
        // Avoid forever loop
        if (strpos($request->getRequestUri(), 'customer/account/ssologin') !== false
            || strpos($request->getRequestUri(), 'customer/account/login') !== false
            || strpos($request->getRequestUri(), 'customer/account/logout') !== false
            || strpos($request->getRequestUri(), 'customer/account/validatesession') !== false
            || strpos($request->getRequestUri(), 'customer/account/refreshcookie') !== false
            || strpos($request->getRequestUri(), 'subscriptions/profiles/select') !== false
        ) {
            return true;
        }

        // TODO get skip URLs from config
        if (strpos($request->getRequestUri(), 'googletag/gaclientid') !== false) {
            return true;
        }

        // Ajax GET request can be skip as it does not impact logic
        if ($request->getServer('REQUEST_METHOD') == 'GET' && $request->isAjax()) {
            return true;
        }

        return false;
    }

    /**
     * @param $customer
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    private function setCookieCustomerToken($customer) {
        $tokenUser = $this->getTokenUser($customer);
        if ($tokenUser) {
            $publicCookieMetadataToken = $this->cookieMetadataFactory->createPublicCookieMetadata()
                ->setPath('/')
                ->setDomain(self::DOMAIN_COOKIE)
                ->setHttpOnly(false);
            $this->cookieManager->setPublicCookie(self::CUSTOMER_ACCESS_TOKEN, $tokenUser, $publicCookieMetadataToken);
        }
    }


    /**
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    public function deleteCookieToken() {
        $publicCookieMetadataToken = $this->cookieMetadataFactory->createPublicCookieMetadata()
            ->setPath('/')
            ->setDomain(self::DOMAIN_COOKIE)
            ->setHttpOnly(false);
        $this->cookieManager->deleteCookie(self::CUSTOMER_ACCESS_TOKEN, $publicCookieMetadataToken);
    }

    /**
     * @param $customer
     * @param $ssoConsumerDbId
     * @return mixed
     */
    private function getTokenUser($customer) {
        $username = $customer->getEmail();
        if ($username) {
            return $this->createCustomerAccessToken($customer);
        }
        return false;
    }


    /**
     * @param $username
     * @param $consumerDbId
     * @return mixed
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function createCustomerAccessToken($customer)
    {
        if ($customer->getId()) {
            return $this->tokenModelFactory->create()->createCustomerToken($customer->getId())->getToken();
        }
        return false;
    }
}
