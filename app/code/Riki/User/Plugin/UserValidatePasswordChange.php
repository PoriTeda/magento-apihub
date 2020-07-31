<?php

namespace Riki\User\Plugin;

class UserValidatePasswordChange
{
    /**
     * @var \Riki\Backend\Helper\Data
     */
    protected $_helperUser;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $_encryptor;

    public function __construct(
        \Riki\Backend\Helper\Data $helperUser,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        array $data = []
    ) {
        $this->_helperUser = $helperUser;
        $this->_encryptor = $encryptor;
    }

    /**
     * Validate Password Change.
     *
     * @return array|bool
     */
    protected function aroundValidatePasswordChange($subject, \Closure $proceed)
    {
        $proceed();
        $password = $subject->getPassword();
        if ($password && !$subject->getForceNewPassword() && $subject->getId()) {
            $errorMessage = __('Sorry, but this password has already been used. Please create another.');
            // Check if password is equal to the current one
            if ($this->_encryptor->isValidHash($password, $subject->getOrigData('password'))) {
                return [$errorMessage];
            }
            $passDictionary = $this->_helperUser->checkPassDictionary($password);
            // Check pass exit in Dictionary
            if ($passDictionary == true) {
                $errorMessage = __('This password is not acceptable.');

                return [$errorMessage];
            }
            $user = $this->getCurrentUser($subject->getId());
            $helper = $this->_helperUser;
            if ($helper->checkPasswordExits($password, $user->getPreviousPassword())) {
                return [$errorMessage];
            }

            // Check whether password was used before
            $passwordHash = $this->_encryptor->getHash($password, false);
            foreach ($subject->getResource()->getOldPasswords($this) as $oldPasswordHash) {
                if ($passwordHash === $oldPasswordHash) {
                    return [$errorMessage];
                }
            }
        }

        return true;
    }
}
