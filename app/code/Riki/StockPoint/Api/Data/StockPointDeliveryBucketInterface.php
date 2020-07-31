<?php


namespace Riki\StockPoint\Api\Data;

interface StockPointDeliveryBucketInterface
{

    const DELIVERY_DATE = 'delivery_date';
    const FIRSTNAME = 'firstname';
    const STREET = 'street';
    const FIRSTNAME_KANA = 'firstname_kana';
    const TELEPHONE = 'telephone';
    const PROFILE_BUCKET_ID = 'profile_bucket_id';
    const DELIVERY_BUCKET_ID = 'delivery_bucket_id';
    const LASTNAME_KANA = 'lastname_kana';
    const REGION_ID = 'region_id';
    const EXPORT_DATE = 'export_date';
    const LASTNAME = 'lastname';
    const POSTCODE = 'postcode';
    const STOCK_POINT_DELIVERY_BUCKET_ID = 'stock_point_delivery_bucket_id';

    /**
     * Get stock_point_delivery_bucket_id
     * @return string|null
     */
    public function getStockPointDeliveryBucketId();

    /**
     * Set stock_point_delivery_bucket_id
     * @param string $stockPointDeliveryBucketId
     * @return \Riki\StockPoint\Api\Data\StockPointDeliveryBucketInterface
     */
    public function setStockPointDeliveryBucketId($stockPointDeliveryBucketId);

    /**
     * Get delivery_bucket_id
     * @return string|null
     */
    public function getDeliveryBucketId();

    /**
     * Set delivery_bucket_id
     * @param string $deliveryBucketId
     * @return \Riki\StockPoint\Api\Data\StockPointDeliveryBucketInterface
     */
    public function setDeliveryBucketId($deliveryBucketId);

    /**
     * Get profile_bucket_id
     * @return string|null
     */
    public function getProfileBucketId();

    /**
     * Set profile_bucket_id
     * @param string $profileBucketId
     * @return \Riki\StockPoint\Api\Data\StockPointDeliveryBucketInterface
     */
    public function setProfileBucketId($profileBucketId);

    /**
     * Get delivery_date
     * @return string|null
     */
    public function getDeliveryDate();

    /**
     * Set delivery_date
     * @param string $deliveryDate
     * @return \Riki\StockPoint\Api\Data\StockPointDeliveryBucketInterface
     */
    public function setDeliveryDate($deliveryDate);

    /**
     * Get export_date
     * @return string|null
     */
    public function getExportDate();

    /**
     * Set export_date
     * @param string $exportDate
     * @return \Riki\StockPoint\Api\Data\StockPointDeliveryBucketInterface
     */
    public function setExportDate($exportDate);

    /**
     * Get firstname
     * @return string|null
     */
    public function getFirstname();

    /**
     * Set firstname
     * @param string $firstname
     * @return \Riki\StockPoint\Api\Data\StockPointDeliveryBucketInterface
     */
    public function setFirstname($firstname);

    /**
     * Get firstname_kana
     * @return string|null
     */
    public function getFirstnameKana();

    /**
     * Set firstname_kana
     * @param string $firstnameKana
     * @return \Riki\StockPoint\Api\Data\StockPointDeliveryBucketInterface
     */
    public function setFirstnameKana($firstnameKana);

    /**
     * Get lastname
     * @return string|null
     */
    public function getLastname();

    /**
     * Set lastname
     * @param string $lastname
     * @return \Riki\StockPoint\Api\Data\StockPointDeliveryBucketInterface
     */
    public function setLastname($lastname);

    /**
     * Get lastname_kana
     * @return string|null
     */
    public function getLastnameKana();

    /**
     * Set lastname_kana
     * @param string $lastnameKana
     * @return \Riki\StockPoint\Api\Data\StockPointDeliveryBucketInterface
     */
    public function setLastnameKana($lastnameKana);

    /**
     * Get street
     * @return string|null
     */
    public function getStreet();

    /**
     * Set street
     * @param string $street
     * @return \Riki\StockPoint\Api\Data\StockPointDeliveryBucketInterface
     */
    public function setStreet($street);

    /**
     * Get region_id
     * @return string|null
     */
    public function getRegionId();

    /**
     * Set region_id
     * @param string $regionId
     * @return \Riki\StockPoint\Api\Data\StockPointDeliveryBucketInterface
     */
    public function setRegionId($regionId);

    /**
     * Get postcode
     * @return string|null
     */
    public function getPostcode();

    /**
     * Set postcode
     * @param string $postcode
     * @return \Riki\StockPoint\Api\Data\StockPointDeliveryBucketInterface
     */
    public function setPostcode($postcode);

    /**
     * Get telephone
     * @return string|null
     */
    public function getTelephone();

    /**
     * Set telephone
     * @param string $telephone
     * @return \Riki\StockPoint\Api\Data\StockPointDeliveryBucketInterface
     */
    public function setTelephone($telephone);
}
