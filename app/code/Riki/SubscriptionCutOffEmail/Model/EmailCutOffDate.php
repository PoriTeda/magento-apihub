<?php 
namespace Riki\SubscriptionCutOffEmail\Model;
use Riki\SubscriptionCutOffEmail\Api\Data\EmailCutOffDateInterface;

class EmailCutOffDate extends \Magento\Framework\Model\AbstractModel implements EmailCutOffDateInterface
{

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\SubscriptionCutOffEmail\Model\ResourceModel\EmailCutOffDate');
    }

    /**
     * Set profile id
     * @param int $profileId
     * @return $this
     */
    public function setProfileId($profileId)
    {
        return $this->setData(self::PROFILE_ID,$profileId);
    }

    /**
     * Get profile Id
     *
     * @return mixed|null
     */
    public function getProfileId()
    {
        return $this->_get(self::PROFILE_ID);
    }

    /**
     * Set cut off date
     * @param string $cutOffDate
     * @return $this
     */
    public function setCutOffDate($cutOffDate)
    {
        return $this->setData(self::CUT_OFF_DATE,$cutOffDate);
    }

    /**
     * Get cut off date
     * @return mixed|null
     */
    public function getCutOffDate()
    {
        return $this->_get(self::CUT_OFF_DATE);
    }

    /**
     * Set Email
     * @param string $email
     * @return $this
     */
    public function setEmail($email)
    {
        return $this->setData(self::EMAIL_LOG,$email);
    }

    /**
     * Get email
     * @return mixed|null
     */
    public function getEmail()
    {
        return $this->_get(self::EMAIL_LOG);
    }



}