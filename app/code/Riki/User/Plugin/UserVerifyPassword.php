<?php

namespace Riki\User\Plugin;

class UserVerifyPassword
{
    protected $_encryptor;

    public function __construct(
        \Magento\Framework\Encryption\EncryptorInterface $encryptor
    )
    {
        $this->_encryptor = $encryptor;
    }

    /*public function beforeVerifyIdentity(\Magento\User\Model\User $subject, $password)
    {
        if ($this->isValidHash($password, $subject->getPassword())) {
            $subject->setPassword($password)
                ->setData('force_new_password', true)
                ->save();
        }
        return [$password];
    }*/

    public function aroundVerifyIdentity(\Magento\User\Model\User $subject, \Closure $proceed, $password)
    {
        if ($this->isValidHash($password, $subject->getPassword())) {
            if ($subject->getReloadAclFlag()) {
                return true;
            } else {
                $subject->setPassword($password)
                    ->setData('force_new_password', true)
                    ->save();
            }
        }
        return $proceed($password);
    }

    public function isValidHash($password, $hash)
    {
        $passwordHash = hash('sha1', $password);

        return $passwordHash === $hash;
    }
}
