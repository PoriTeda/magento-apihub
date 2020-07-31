<?php

namespace Riki\Customer\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class BeforeSsoLogoutRedirect implements ObserverInterface
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $session;

    /**
     * @var \Riki\Customer\Model\Cookie
     */
    protected $cookie;

    /**
     * BeforeSsoLogoutRedirect constructor.
     * @param \Magento\Customer\Model\Session $session
     * @param \Riki\Customer\Model\Cookie $cookie
     */
    public function __construct(
        \Magento\Customer\Model\Session $session,
        \Riki\Customer\Model\Cookie $cookie
    )
    {
        $this->session = $session;
        $this->cookie = $cookie;
    }

    /**
     * Log out customer before he is redirected to SSO.
     * PHPSESSID will be renew.
     *
     * @param Observer $observer
     *
     * @return void
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $fullActionName = $observer->getFullActionName();

        if ($fullActionName == 'customer_account_logout') {
            $this->cookie->deleteCookieToken();
            $this->session->logout();
            $this->cookie->sendInvalidatePrivateCache();
            $this->cookie->sendNoCache();
        }
    }
}
