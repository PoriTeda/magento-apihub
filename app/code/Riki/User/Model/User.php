<?php

namespace Riki\User\Model;

use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Backend\Model\Auth\Credential\StorageInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\ObjectManagerInterface; // @codingStandardsIgnoreLine
use Magento\Store\Model\Store;
use Magento\User\Api\Data\UserInterface;
use Magento\User\Model\UserValidationRules;

class User extends \Magento\User\Model\User
{



    /**
     * Validate Password Change
     *
     * @return array|bool
     */
    protected function validatePasswordChange()
    {
        $password = $this->getPassword();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // @codingStandardsIgnoreLine
        $helper = $objectManager->create('\Riki\Backend\Helper\Data');
        if ($password && !$this->getForceNewPassword() && $this->getId()) {
            $errorMessage = __('You can\'t use same password as previous.');
            // Check if password is equal to the current one
            if ($this->_encryptor->isValidHash($password, $this->getOrigData('password'))) {
                return [$errorMessage];
            }
            $passDictionary =  $helper->checkPassDictionary($password);
            // Check pass exit in Dictionary
            if($passDictionary == true){
                $errorMessage = __('This password is not acceptable.');
                return [$errorMessage];
            }
            $user = $this->getCurrentUser($this->getId());

            if ($helper->checkPasswordExits($password, $user->getPreviousPassword())) {
                return [$errorMessage];
            }

            // Check whether password was used before
            $passwordHash = $this->_encryptor->getHash($password, false);
            foreach ($this->getResource()->getOldPasswords($this) as $oldPasswordHash) {
                if ($passwordHash === $oldPasswordHash) {
                    return [$errorMessage];
                }
            }
        }
        return true;
    }

    /**
     * Get current user
     *
     * @param string $userId user id
     *
     * @return $this
     */
    public function getCurrentUser($userId)
    {
        return $this->load($userId);
    }
}
