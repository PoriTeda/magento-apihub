<?php
namespace Riki\User\Observer\Backend;

use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Message\ManagerInterface;
use Magento\User\Model\ResourceModel\User as ResourceUser;
use Magento\User\Model\User;
use Magento\Framework\Event\ObserverInterface;
use Magento\User\Model\UserFactory;

/**
 * User backend observer model for authentication
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AuthObserver implements ObserverInterface
{

    /**
     * Backend authorization session
     *
     * @var Session
     */
    protected $authSession;

    /**
     * Message manager interface
     *
     * @var ManagerInterface
     */
    protected $messageManager;

    protected $serialize;

    /**
     * @param Session $authSession
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        Session $authSession,
        ManagerInterface $messageManager,
        \Magento\Framework\Serialize\Serializer\Json $serialize
    ) {
        $this->authSession = $authSession;
        $this->messageManager = $messageManager;
        $this->serialize = $serialize;
    }

    /**
     * Admin locking and password hashing upgrade logic implementation
     *
     * @param EventObserver $observer
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(EventObserver $observer)
    {
        /** @var User $user */
        $user = $observer->getEvent()->getUser();

        if($this->needToResetPassword($user)){

            $this->messageManager->addNoticeMessage(__('It\'s time to change your password.'));

            $this->authSession->setNeedToResetPasswordAfterFirstLogin(true);
        }else{
            $this->authSession->unsNeedToResetPasswordAfterFirstLogin();
        }
    }

    /**
     * @param User $user
     * @return bool
     */
    protected function needToResetPassword(User $user){
return false;
        $extra = $user->getExtra();

        if (is_string($user->getExtra())) {
            $extra = $this->serialize->unserialize($extra);
        }

        if(!is_array($extra) || !isset($extra['reset_password']) || !$extra['reset_password'])
            return true;

        return false;
    }
}
