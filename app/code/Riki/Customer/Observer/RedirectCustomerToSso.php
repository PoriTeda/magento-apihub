<?php

namespace Riki\Customer\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class RedirectCustomerToSso implements ObserverInterface
{
    /**
     * @var \Riki\Customer\Model\SsoConfig
     */
    protected $ssoConfig;

    /**
     * @var \Magento\Framework\App\ActionFlag
     */
    protected $actionFlag;

    /**
     * @var \Riki\Customer\Helper\SsoUrl
     */
    protected $ssoUrl;

    /**
     * @var \Magento\Framework\Event\Manager
     */
    protected $eventManager;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Customer\Model\Url
     */
    protected $customerUrl;

    /**
     * @var \Magento\Framework\App\Response\RedirectInterface
     */
    protected $redirect;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * RedirectCustomerToSso constructor.
     * @param \Riki\Customer\Model\SsoConfig $ssoConfig
     * @param \Magento\Framework\App\ActionFlag $actionFlag
     * @param \Riki\Customer\Helper\SsoUrl $ssoUrl
     * @param \Magento\Framework\Event\Manager $eventManager
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Model\Url $customerUrl
     * @param \Magento\Framework\App\Response\RedirectInterface $redirect
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Riki\Customer\Model\SsoConfig $ssoConfig,
        \Magento\Framework\App\ActionFlag $actionFlag,
        \Riki\Customer\Helper\SsoUrl $ssoUrl,
        \Magento\Framework\Event\Manager $eventManager,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Model\Url $customerUrl,
        \Magento\Framework\App\Response\RedirectInterface $redirect,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Checkout\Model\Session $checkoutSession
    )
    {
        $this->ssoConfig = $ssoConfig;
        $this->actionFlag = $actionFlag;
        $this->ssoUrl = $ssoUrl;
        $this->eventManager = $eventManager;
        $this->customerSession = $customerSession;
        $this->customerUrl = $customerUrl;
        $this->redirect = $redirect;
        $this->storeManager = $storeManager;
        $this->urlBuilder = $urlBuilder;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->ssoConfig->isEnabled()) {
            $controllerAction = $observer->getControllerAction();
            $request = $observer->getRequest();

            $returnUrl = $this->redirect->getRefererUrl();
            $checkoutRefererUrl = $this->checkoutSession->getCheckoutRefererUrl();
            if(isset($checkoutRefererUrl) && $checkoutRefererUrl !== ""){
                $returnUrl = $checkoutRefererUrl;
            }
            $this->checkoutSession->unsCheckoutRefererUrl();
            $currentStore = $this->storeManager->getStore();
            if ($currentStore->getBaseUrl() == $returnUrl
                || $currentStore->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB) == $returnUrl
            ) {
                $returnUrl = $this->customerUrl->getDashboardUrl();
            }

            switch ($request->getFullActionName()) {
                case 'customer_account_login':
                    if (!$this->customerSession->isLoggedIn()) {
                        $this->customerSession->unsHasMissingInformation();
                        $ssoUrl = $this->ssoUrl->getLoginUrl($returnUrl);
                    }
                    break;
                case 'customer_account_logout':
                    //if ($this->customerSession->isLoggedIn()) {
                        $ssoUrl = $this->ssoUrl->getLogoutUrl();
                   // }
                    break;
                case 'customer_account_create':
                    if (!$this->customerSession->isLoggedIn()) {
                        $ssoUrl = $this->ssoUrl->getRegisterUrl($returnUrl);
                    }
                    break;
            }

            if (isset($ssoUrl)) {
                $this->eventManager->dispatch('riki_customer_sso_redirect', [
                    'full_action_name' => $request->getFullActionName()
                ]);

                $controllerAction->getResponse()
                    ->setRedirect($ssoUrl);

                $this->actionFlag->set('', \Magento\Framework\App\ActionInterface::FLAG_NO_DISPATCH, true);
            }
        }
    }
}
