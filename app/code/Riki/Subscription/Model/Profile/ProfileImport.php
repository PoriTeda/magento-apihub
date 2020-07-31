<?php
namespace Riki\Subscription\Model\Profile;

use Riki\Subscription\Api\Data\ProfileInterface;
use Magento\Framework\Model\AbstractModel;

class ProfileImport extends AbstractModel implements ProfileInterface
{
    protected function _construct()
    {
        $this->_init('Riki\Subscription\Model\Profile\ResourceModel\Profile');
    }

    /**
     * {@inheritdoc}
     */
    public function getSubProfileID()
    {
        return $this->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function setSubProfileID($id)
    {
        return $this->setId($id);
    }

    /**
     * @return int
     */
    public function getCustomerId()
    {
        return $this->_getData(self::CUSTOMER_ID);
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setCustomerId($id)
    {
        return $this->setData(self::CUSTOMER_ID, $id);
    }

    /**
     * @return string
     */
    public function getCourseName()
    {
        return $this->_getData(self::COURSE_NAME);
    }

    /**
     * @param string $string
     * @return $this
     */
    public function setCourseName($string)
    {
        return $this->setData(self::COURSE_NAME, $string);
    }

    /**
     * @return string
     */
    public function getUpdatedDate()
    {
        return $this->_getData(self::UPDATED_DATE);
    }

    /**
     * @param string $string
     * @return $this
     */
    public function setUpdatedDate($string)
    {
        return $this->setData(self::UPDATED_DATE, $string);
    }

    /**
     * @return string
     */
    public function getFrequencyUnit()
    {
        return $this->_getData(self::FREQUENCY_UNIT);
    }

    /**
     * @param string $string
     * @return $this
     */
    public function setFrequencyUnit($string)
    {
        return $this->setData(self::FREQUENCY_UNIT, $string);
    }

    /**
     * @return string
     */
    public function getFrequencyInterval()
    {
        return $this->_getData(self::FREQUENCY_INTERVAL);
    }

    /**
     * @param string $string
     * @return $this
     */
    public function setFrequencyInterval($string)
    {
        return $this->setData(self::FREQUENCY_INTERVAL, $string);
    }

    /**
     * {@inheritdoc}
     */
    public function getDurationUnit()
    {
        return $this->_getData(self::DURATION_UNIT);
    }

    /**
     * {@inheritdoc}
     */
    public function setDurationUnit($string)
    {
        return $this->setData(self::DURATION_UNIT, $string);
    }

    /**
     * {@inheritdoc}
     */
    public function getDurationInterval()
    {
        return $this->_getData(self::DURATION_INTERVAL);
    }

    /**
     * {@inheritdoc}
     */
    public function setDurationInterval($string)
    {
        return $this->setData(self::DURATION_INTERVAL, $string);
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentMethod()
    {
        return $this->_getData(self::PAYMENT_METHOD);
    }

    /**
     * {@inheritdoc}
     */
    public function setPaymentMethod($string)
    {
        return $this->setData(self::PAYMENT_METHOD, $string);
    }

    public function getCourseId()
    {
        return $this->getData('course_id');
    }


    /**
     * Get list course setting for WATSON API
     *
     * @return string[]
     */
    public function getCourseSetting()
    {
        return true;
    }

    /**
     * @param array $array
     * @return $this
     */
    public function setCourseSetting($array)
    {
        return $array;
    }


    /**
     * @return string
     */
    public function getProfileProductCart(){
        return true;
    }

    /**
     * @param string $string
     * @return $this
     */
    public function setProfileProductCart($string){
         return $string;
    }


    /**
     * {@inheritdoc}
     */
    public function getStatus()
    {
        return $this->_getData(self::STATUS);
    }

    /**
     * {@inheritdoc}
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * {@inheritdoc}
     */
    public function getSubProfileCartOrder()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function setSubProfileCartOrder(array $subProfileCartOrder = null)
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubProfileFrequencyID()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function setSubProfileFrequencyID($id)
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getChangeType()
    {
        return $this->getData('change_type');
    }

    /**
     * {@inheritdoc}
     */
    public function setChangeType($type)
    {
        return $this->setData('change_type', $type);
    }

    /**
     * {@inheritdoc}
     */
    public function getLastUpdate()
    {
        return $this->getData('updated_date');
    }

    /**
     * {@inheritdoc}
     */
    public function setLastUpdate($date)
    {
        return $this->setData('updated_date', $date);
    }

    /**
     * {@inheritdoc}
     */
    public function setConsumerCustomerID($id)
    {
        return $this->setData('consumer_db_id', $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getConsumerCustomerID()
    {
        return $this->getData('consumer_db_id');
    }

    /**
     *
     * @return array|mixed
     */
    public function validateData(){

        return $abc[] = __('customer_id is invalid');
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setOldProfileId($id){
        return $this->setData('old_profile_id', $id);
    }

    /**
     * {@inheritdoc}
     */
    public function getOldProfileId()
    {
        return $this->getData('old_profile_id');
    }


}