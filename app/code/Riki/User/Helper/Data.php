<?php
namespace Riki\User\Helper;

class Data extends  \Magento\Framework\App\Helper\AbstractHelper
{
    const TAG_SEPARATE_PASSWORD_HASH = "___";
    const TAG_NUMBER_PREVIOUS_PASSWORD_CHECK = "admin/security/number_previous_password";

    protected $_encryptor;

    /**
     * Init
     *
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor encrytor
     * @param \Magento\Framework\App\Helper\Context            $context   context
     */
    public function __construct(
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Framework\App\Helper\Context $context
    ) {
        parent::__construct($context);
        $this->_encryptor = $encryptor;

    }

    /**
     * Hash password to save
     *
     * @param string $password current password
     *
     * @return string
     */
    public function hashPassword($password)
    {
        return $this->_encryptor->getHash($password, false);
    }

    /**
     * Check password change is exit
     *
     * @param string $currentPass            current password
     * @param string $strPreviousPasswordHas password save in db
     *
     * @return bool
     */
    public function checkPasswordExits($currentPass, $strPreviousPasswordHas)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $numberPreviousPassCheck = $this->scopeConfig->getValue(self::TAG_NUMBER_PREVIOUS_PASSWORD_CHECK, $storeScope);
        $currentPasswordHash = $this->hashPassword($currentPass);
        if (strpos($strPreviousPasswordHas, self::TAG_SEPARATE_PASSWORD_HASH)) {
            $arrPassWordHash = explode(self::TAG_SEPARATE_PASSWORD_HASH, $strPreviousPasswordHas);
            if (count($arrPassWordHash) < $numberPreviousPassCheck) {
                $numberPreviousPassCheck = count($arrPassWordHash);
                for ($i=0; $i < $numberPreviousPassCheck; $i++) {
                    if ($arrPassWordHash[$i] == $currentPasswordHash) {
                        return true;
                    }
                }
            } else {
                $limitPassWordCheck = (count($arrPassWordHash) - $numberPreviousPassCheck);
                $begin = count($arrPassWordHash) - 1;
                for ($i= $begin; $i >= $limitPassWordCheck; $i-- ) {
                    if ($arrPassWordHash[$i] == $currentPasswordHash) {
                        return true;
                    }
                }
            }
        } else {
            if ($strPreviousPasswordHas != "") {
                if ($currentPasswordHash == $strPreviousPasswordHas) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Append current password to end of string previous password in db
     *
     * @param string $previousDdPasswordHash previous password save in db
     * @param string $currentPassword        current password
     *
     * @return string
     */
    public function appendToPreviousPassword($previousDdPasswordHash, $currentPassword)
    {
        if ($previousDdPasswordHash == "") {
            return $this->hashPassword($currentPassword);
        } else {
            return $previousDdPasswordHash.self::TAG_SEPARATE_PASSWORD_HASH.$this->hashPassword($currentPassword);
        }
    }
}