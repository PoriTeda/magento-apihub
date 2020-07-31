<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\WebsiteRestriction\Model;

use Magento\Customer\Model\Url;

/**
 * @api
 * @since 100.0.2
 */
class Restrictor
{
    /**
     * @var \Magento\WebsiteRestriction\Model\ConfigInterface
     */
    protected $_config;

    /**
     * @var \Magento\Framework\UrlFactory
     */
    protected $_urlFactory;

    /**
     * @var \Magento\Framework\App\ActionFlag
     */
    protected $_actionFlag;

    /**
     * @var \Magento\Customer\Model\Registration
     */
    protected $registration;

    /**
     * @var \Magento\Framework\Session\Generic
     */
    protected $_session;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Customer\Model\Url
     */
    protected $customerUrl;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @param ConfigInterface $config
     * @param \Magento\Customer\Model\Registration $registration
     * @param \Magento\Framework\Session\Generic $session
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\UrlFactory $urlFactory
     * @param \Magento\Framework\App\ActionFlag $actionFlag
     * @param \Magento\Customer\Model\Url $customerUrl
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        \Magento\WebsiteRestriction\Model\ConfigInterface $config,
        \Magento\Customer\Model\Registration $registration,
        \Magento\Framework\Session\Generic $session,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\UrlFactory $urlFactory,
        \Magento\Framework\App\ActionFlag $actionFlag,
        \Magento\Customer\Model\Url $customerUrl,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->customerUrl = $customerUrl;
        $this->_config = $config;
        $this->registration = $registration;
        $this->_customerSession = $customerSession;
        $this->_session = $session;
        $this->_scopeConfig = $scopeConfig;
        $this->_urlFactory = $urlFactory;
        $this->_actionFlag = $actionFlag;
    }

    /**
     * Restrict access to website
     *
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\App\ResponseInterface $response
     * @param bool $isCustomerLoggedIn
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function restrict($request, $response, $isCustomerLoggedIn)
    {
        $actionFullName = strtolower($request->getFullActionName());

        switch ($this->_config->getMode()) {
            // show only landing page with 503 or 200 code
            case \Magento\WebsiteRestriction\Model\Mode::ALLOW_NONE:
                if ($actionFullName !== 'restriction_index_stub') {
                    $request->setModuleName('restriction')
                        ->setControllerName('index')
                        ->setActionName('stub')
                        ->setDispatched(false);
                    return;
                }
                $httpStatus = $this->_config->getHTTPStatusCode();
                if (\Magento\WebsiteRestriction\Model\Mode::HTTP_503 === $httpStatus) {
                    $response->setStatusHeader(503, '1.1', 'Service Unavailable');
                }
                break;

            case \Magento\WebsiteRestriction\Model\Mode::ALLOW_REGISTER:
                // break intentionally omitted

                //redirect to landing page/login
            case \Magento\WebsiteRestriction\Model\Mode::ALLOW_LOGIN:
                if (!$isCustomerLoggedIn && !$this->_customerSession->isLoggedIn()) {
                    // see whether redirect is required and where
                    $redirectUrl = false;
                    $allowedActionNames = $this->_config->getGenericActions();
                    if ($this->registration->isAllowed()) {
                        $allowedActionNames = array_merge($allowedActionNames, $this->_config->getRegisterActions());
                    }

                    array_walk(
                        $allowedActionNames,
                        function (&$item) {
                            $item = strtolower($item);
                        }
                    );

                    // to specified landing page
                    $restrictionRedirectCode = $this->_config->getHTTPRedirectCode();
                    if (\Magento\WebsiteRestriction\Model\Mode::HTTP_302_LANDING === $restrictionRedirectCode) {
                        $cmsPageViewAction = 'cms_page_view';
                        $allowedActionNames[] = $cmsPageViewAction;
                        $pageIdentifier = $this->_config->getLandingPageCode();
                        // Restrict access to CMS pages too
                        if (!in_array($actionFullName, $allowedActionNames)
                            || $actionFullName === $cmsPageViewAction
                            && $request->getAlias('rewrite_request_path') !== $pageIdentifier
                        ) {
                            $redirectUrl = $this->_urlFactory->create()->getUrl('', ['_direct' => $pageIdentifier]);
                        }
                    } elseif (!in_array($actionFullName, $allowedActionNames)) {
                        // to login form
                        $redirectUrl = $this->_urlFactory->create()->getUrl('customer/account/login');
                    }

                    if ($redirectUrl) {
                        $response->setRedirect($redirectUrl);
                        $this->_actionFlag->set('', \Magento\Framework\App\ActionInterface::FLAG_NO_DISPATCH, true);
                    }
                    $redirectToDashboard = $this->_scopeConfig->isSetFlag(
                        Url::XML_PATH_CUSTOMER_STARTUP_REDIRECT_TO_DASHBOARD,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                    );
                    if ($redirectToDashboard) {
                        $afterLoginUrl = $this->customerUrl->getDashboardUrl();
                    } else {
                        $afterLoginUrl = $this->_urlFactory->create()->getUrl();
                    }
                    $this->_session->setWebsiteRestrictionAfterLoginUrl($afterLoginUrl);
                } elseif ($this->_session->hasWebsiteRestrictionAfterLoginUrl()) {
                    $response->setRedirect($this->_session->getWebsiteRestrictionAfterLoginUrl(true));
                    $this->_actionFlag->set('', \Magento\Framework\App\ActionInterface::FLAG_NO_DISPATCH, true);
                }
                break;
        }
    }
}
