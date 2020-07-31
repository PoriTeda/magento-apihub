<?php
/**
 * *
 *  Backend
 *
 *  PHP version 7
 *
 *  @category RIKI
 *  @package  Riki\Backend
 *  @author   Nestle.co.jp <support@nestle.co.jp>
 *  @license  https://opensource.org/licenses/MIT MIT License
 *  @link     https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\Backend\Controller\Adminhtml\System\Account;

use Magento\Framework\Validator\Exception as ValidatorException;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action;

/**
 * Class Save
 *
 *  @category RIKI
 *  @package  Riki\Backend\Controller\Adminhtml\System\Account
 *  @author   Nestle.co.jp <support@nestle.co.jp>
 *  @license  https://opensource.org/licenses/MIT MIT License
 *  @link     https://github.com/rikibusiness/riki-ecommerce
 */
class Save extends \Magento\Backend\Controller\Adminhtml\System\Account\Save
{

    /**
     *  Property
     *
     * @var \Riki\User\Helper\Data
     */
    protected $helperUser;
    /**
     *   Property
     *
     * @var \Magento\Backend\Model\Locale\Manager
     */
    protected $localManager;
    /**
     *   Property
     *
     * @var \Magento\Framework\Validator\Locale
     */
    protected $localeValidator;
    /**
     * Property
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    /**
     * Property
     *
     * @var \Magento\User\Model\User
     */
    protected $userModel;
    /**
     * Property
     *
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $sessionAuth;

    /**
     * Save constructor.
     *
     * @param Action\Context                        $context         $context
     * @param \Riki\Backend\Helper\Data             $helperUser      $helperUser
     * @param \Magento\User\Model\User              $userModel       $userModel
     * @param \Magento\Backend\Model\Locale\Manager $localManager    $localManager
     * @param \Magento\Framework\Validator\Locale   $localeValidator $localeValidator
     * @param \Magento\Backend\Model\Auth\Session   $sessionAuth     $sessionAuth
     */
    public function __construct(
        Action\Context $context,
        \Riki\Backend\Helper\Data $helperUser,
        \Magento\User\Model\User $userModel,
        \Magento\Backend\Model\Locale\Manager $localManager,
        \Magento\Framework\Validator\Locale $localeValidator,
        \Magento\Backend\Model\Auth\Session $sessionAuth
    ) {
        parent::__construct($context);
        $this->helperUser = $helperUser;
        $this->userModel = $userModel;
        $this->localManager = $localManager;
        $this->localeValidator = $localeValidator;
        $this->sessionAuth = $sessionAuth;
    }
    /**
     * Execute
     *
     * @return $this
     */
    public function execute()
    {

        $helper = $this->helperUser;

        $userId = $this->sessionAuth->getUser()->getId();
        $password = (string)$this->getRequest()->getParam('password');
        $passwordConfirmation = (string)$this->getRequest()->getParam('password_confirmation');
        $passwordCurrent = (string)$this->getRequest()->getParam('current_password');
        $interfaceLocale = (string)$this->getRequest()->getParam('interface_locale', false);



        /**
         * User Model
         *
         * @var \Magento\User\Model\User $user user model
         */
        $user = $this->userModel->load($userId);

        $user->setId($userId)
            ->setUsername($this->getRequest()->getParam('username', false))
            ->setFirstname($this->getRequest()->getParam('firstname', false))
            ->setLastname($this->getRequest()->getParam('lastname', false))
            ->setEmail(strtolower($this->getRequest()->getParam('email', false)));

        if ($this->localeValidator->isValid($interfaceLocale)) {
            $user->setInterfaceLocale($interfaceLocale);
            /**
             * Locale Manager
             *
             * @var \Magento\Backend\Model\Locale\Manager $localeManager local manager
             */
            $localeManager = $this->localManager;
            $localeManager->switchBackendInterfaceLocale($interfaceLocale);
        }
        /**
         * Before updating admin user data, ensure that password of current admin user is entered and is correct
         */
        $currentUserPasswordField = \Magento\User\Block\User\Edit\Tab\Main::CURRENT_USER_PASSWORD_FIELD;
        $currentUserPassword = $this->getRequest()->getParam($currentUserPasswordField);
        $isCurrentUserPasswordValid = !empty($currentUserPassword) && is_string($currentUserPassword);
        try {
            if (!($isCurrentUserPasswordValid && $user->verifyIdentity($currentUserPassword))) {
                throw new AuthenticationException(__('You have entered an invalid password for current user.'));
            }
            if ($password !== '') {

                if ($helper->checkPasswordExits($password, $user->getPreviousPassword())) {
                    $this->messageManager->addError(__("You can't use same password as previous"));
                    $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                    return $resultRedirect->setPath("*/*/");
                }
                $passDictionary =  $helper->checkPassDictionary($password);
                // Check pass exit in Dictionary
                if ($passDictionary === true) {
                    $this->messageManager->addError(__("This password is not acceptable."));
                    $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                    return $resultRedirect->setPath("*/*/");
                }
                $user->setPassword($password);
                $user->setPreviousPassword($helper->appendToPreviousPassword($user->getPreviousPassword(), $password));
                $user->setPasswordConfirmation($passwordConfirmation);
                $user->setCurrentPassword($passwordCurrent);
            }
            try {
                $user->save();
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            }

            /**
             * Send password reset email notification only when password was changed
             */
            if ($password !== '') {
                $user->sendPasswordResetNotificationEmail();
            }
            $this->messageManager->addSuccess(__('You saved the account.'));
        } catch (ValidatorException $e) {
            $this->messageManager->addMessages($e->getMessages());
            if ($e->getMessage()) {
                $this->messageManager->addError($e->getMessage());
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addError(__('An error occurred while saving account.'));
        }

        /**
         * Result redirect
         *
         * @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect result redirect
         */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath("*/*/");
    }
}