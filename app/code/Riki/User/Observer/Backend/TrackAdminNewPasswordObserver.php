<?php

namespace Riki\User\Observer\Backend;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

/**
 * User backend observer model for passwords
 */
class TrackAdminNewPasswordObserver implements ObserverInterface
{

    /**
     * Backend authorization session
     *
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $authSession;

    /**
     * @param \Magento\Backend\Model\Auth\Session $session
     */
    public function __construct(
        \Magento\Backend\Model\Auth\Session $session
    ){
        $this->authSession = $session;
    }

    /**
     * Save reset password flag data
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        /* @var $user \Magento\User\Model\User */
        $user = $observer->getEvent()->getObject();
        if (
            $this->authSession->getUser() &&
            $this->authSession->getUser()->getId() == $user->getId() &&  // be updated by them self
            $user->getId() &&
            $user->getLognum()
        ) {
            $extra = $user->getExtra();

            if(!is_array($extra))
                $extra = [];

            if (
                !isset($extra['reset_password']) ||
                !$extra['reset_password']
            ) {
                $extra['reset_password'] = 1;

                $user->saveExtra($extra);
            }

            $this->authSession->unsNeedToResetPasswordAfterFirstLogin();
        }
    }
}
