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
namespace Riki\Backend\Helper;
/**
 * *
 *  Backend
 *
 *  @category RIKI
 *  @package  Riki\Backend\Helper
 *  @author   Nestle.co.jp <support@nestle.co.jp>
 *  @license  https://opensource.org/licenses/MIT MIT License
 *  @link     https://github.com/rikibusiness/riki-ecommerce
 */
class Data extends  \Magento\Framework\App\Helper\AbstractHelper
{
    const TAG_SEPARATE_PASSWORD_HASH = "___";
    const TAG_NUMBER_PREVIOUS_PASSWORD_CHECK = "admin/security/number_previous_password";
    /**
     * Encrypt
     *
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $encryptor;
    /**
     * Password Collection
     * 
     * @var \Riki\User\Model\ResourceModel\Password\CollectionFactory
     */
    protected $collectionPassword;

    /**
     * Data constructor.
     *
     * @param \Magento\Framework\Encryption\EncryptorInterface          $encryptor          $encryptor
     * @param \Riki\User\Model\ResourceModel\Password\CollectionFactory $collectionPassword $collectionPassword
     * @param \Magento\Framework\App\Helper\Context                     $context            $context
     */
    public function __construct(
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Riki\User\Model\ResourceModel\Password\CollectionFactory $collectionPassword,
        \Magento\Framework\App\Helper\Context $context
    ) {
        parent::__construct($context);
        $this->encryptor = $encryptor;
        $this->collectionPassword = $collectionPassword;

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
        return $this->encryptor->getHash($password, false);
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

    /**
     * Check password exclude
     *
     * @param string $password password deny
     *
     * @return array|bool
     */
    public function checkPassDictionary($password)
    {
        $ngWord = $this->collectionPassword->create();
        $ngWord->addFieldToSelect('ng_word');

        if ($ngWord->getSize()) {
            foreach ($ngWord->getData() as $ngWordData ) {
                if (strpos($password, $ngWordData['ng_word']) !== false) {
                    return true;
                }
            }
        }
        return false;
    }
}