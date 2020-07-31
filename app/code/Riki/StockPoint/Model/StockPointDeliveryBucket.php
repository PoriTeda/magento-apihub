<?php


namespace Riki\StockPoint\Model;

use Riki\StockPoint\Api\Data\StockPointDeliveryBucketInterface;
use Magento\Framework\Model\AbstractModel;

class StockPointDeliveryBucket extends AbstractModel implements StockPointDeliveryBucketInterface
{

    protected $_eventPrefix = 'stock_point_delivery_bucket';

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Riki\StockPoint\Model\ResourceModel\StockPointDeliveryBucket::class);
    }

    /**
     * Get stock_point_delivery_bucket_id
     * @return string
     */
    public function getStockPointDeliveryBucketId()
    {
        return $this->getData(self::STOCK_POINT_DELIVERY_BUCKET_ID);
    }

    /**
     * Set stock_point_delivery_bucket_id
     * @param string $stockPointDeliveryBucketId
     * @return \Riki\StockPoint\Api\Data\StockPointDeliveryBucketInterface
     */
    public function setStockPointDeliveryBucketId($stockPointDeliveryBucketId)
    {
        return $this->setData(self::STOCK_POINT_DELIVERY_BUCKET_ID, $stockPointDeliveryBucketId);
    }

    /**
     * Get delivery_bucket_id
     * @return string
     */
    public function getDeliveryBucketId()
    {
        return $this->getData(self::DELIVERY_BUCKET_ID);
    }

    /**
     * Set delivery_bucket_id
     * @param string $deliveryBucketId
     * @return \Riki\StockPoint\Api\Data\StockPointDeliveryBucketInterface
     */
    public function setDeliveryBucketId($deliveryBucketId)
    {
        return $this->setData(self::DELIVERY_BUCKET_ID, $deliveryBucketId);
    }

    /**
     * Get profile_bucket_id
     * @return string
     */
    public function getProfileBucketId()
    {
        return $this->getData(self::PROFILE_BUCKET_ID);
    }

    /**
     * Set profile_bucket_id
     * @param string $profileBucketId
     * @return \Riki\StockPoint\Api\Data\StockPointDeliveryBucketInterface
     */
    public function setProfileBucketId($profileBucketId)
    {
        return $this->setData(self::PROFILE_BUCKET_ID, $profileBucketId);
    }

    /**
     * Get delivery_date
     * @return string
     */
    public function getDeliveryDate()
    {
        return $this->getData(self::DELIVERY_DATE);
    }

    /**
     * Set delivery_date
     * @param string $deliveryDate
     * @return \Riki\StockPoint\Api\Data\StockPointDeliveryBucketInterface
     */
    public function setDeliveryDate($deliveryDate)
    {
        return $this->setData(self::DELIVERY_DATE, $deliveryDate);
    }

    /**
     * Get export_date
     * @return string
     */
    public function getExportDate()
    {
        return $this->getData(self::EXPORT_DATE);
    }

    /**
     * Set export_date
     * @param string $exportDate
     * @return \Riki\StockPoint\Api\Data\StockPointDeliveryBucketInterface
     */
    public function setExportDate($exportDate)
    {
        return $this->setData(self::EXPORT_DATE, $exportDate);
    }

    /**
     * Get firstname
     * @return string
     */
    public function getFirstname()
    {
        return $this->getData(self::FIRSTNAME);
    }

    /**
     * Set firstname
     * @param string $firstname
     * @return \Riki\StockPoint\Api\Data\StockPointDeliveryBucketInterface
     */
    public function setFirstname($firstname)
    {
        return $this->setData(self::FIRSTNAME, $firstname);
    }

    /**
     * Get firstname_kana
     * @return string
     */
    public function getFirstnameKana()
    {
        return $this->getData(self::FIRSTNAME_KANA);
    }

    /**
     * Set firstname_kana
     * @param string $firstnameKana
     * @return \Riki\StockPoint\Api\Data\StockPointDeliveryBucketInterface
     */
    public function setFirstnameKana($firstnameKana)
    {
        return $this->setData(self::FIRSTNAME_KANA, $firstnameKana);
    }

    /**
     * Get lastname
     * @return string
     */
    public function getLastname()
    {
        return $this->getData(self::LASTNAME);
    }

    /**
     * Set lastname
     * @param string $lastname
     * @return \Riki\StockPoint\Api\Data\StockPointDeliveryBucketInterface
     */
    public function setLastname($lastname)
    {
        return $this->setData(self::LASTNAME, $lastname);
    }

    /**
     * Get lastname_kana
     * @return string
     */
    public function getLastnameKana()
    {
        return $this->getData(self::LASTNAME_KANA);
    }

    /**
     * Set lastname_kana
     * @param string $lastnameKana
     * @return \Riki\StockPoint\Api\Data\StockPointDeliveryBucketInterface
     */
    public function setLastnameKana($lastnameKana)
    {
        return $this->setData(self::LASTNAME_KANA, $lastnameKana);
    }

    /**
     * Get street
     * @return string
     */
    public function getStreet()
    {
        return $this->getData(self::STREET);
    }

    /**
     * Set street
     * @param string $street
     * @return \Riki\StockPoint\Api\Data\StockPointDeliveryBucketInterface
     */
    public function setStreet($street)
    {
        return $this->setData(self::STREET, $street);
    }

    /**
     * Get region_id
     * @return string
     */
    public function getRegionId()
    {
        return $this->getData(self::REGION_ID);
    }

    /**
     * Set region_id
     * @param string $regionId
     * @return \Riki\StockPoint\Api\Data\StockPointDeliveryBucketInterface
     */
    public function setRegionId($regionId)
    {
        return $this->setData(self::REGION_ID, $regionId);
    }

    /**
     * Get postcode
     * @return string
     */
    public function getPostcode()
    {
        return $this->getData(self::POSTCODE);
    }

    /**
     * Set postcode
     * @param string $postcode
     * @return \Riki\StockPoint\Api\Data\StockPointDeliveryBucketInterface
     */
    public function setPostcode($postcode)
    {
        return $this->setData(self::POSTCODE, $postcode);
    }

    /**
     * Get telephone
     * @return string
     */
    public function getTelephone()
    {
        return $this->getData(self::TELEPHONE);
    }

    /**
     * Set telephone
     * @param string $telephone
     * @return \Riki\StockPoint\Api\Data\StockPointDeliveryBucketInterface
     */
    public function setTelephone($telephone)
    {
        return $this->setData(self::TELEPHONE, $telephone);
    }
}
