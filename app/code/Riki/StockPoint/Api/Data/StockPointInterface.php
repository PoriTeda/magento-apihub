<?php


namespace Riki\StockPoint\Api\Data;

interface StockPointInterface
{

    const STOCK_POINT_ID = 'stock_point_id';
    const FIRSTNAME = 'firstname';
    const STREET = 'street';
    const FIRSTNAME_KANA = 'firstname_kana';
    const TELEPHONE = 'telephone';
    const LASTNAME_KANA = 'lastname_kana';
    const REGION_ID = 'region_id';
    const POSTCODE = 'postcode';
    const LASTNAME = 'lastname';
    const EXTERNAL_STOCK_POINT_ID = 'external_stock_point_id';

    /**
     * Get stock_point_id
     * @return string|null
     */
    public function getStockPointId();

    /**
     * Set stock_point_id
     * @param string $stockPointId
     * @return \Riki\StockPoint\Api\Data\StockPointInterface
     */
    public function setStockPointId($stockPointId);

    /**
     * Get external_stock_point_id
     * @return string|null
     */
    public function getExternalStockPointId();

    /**
     * Set external_stock_point_id
     * @param string $externalStockPointId
     * @return \Riki\StockPoint\Api\Data\StockPointInterface
     */
    public function setExternalStockPointId($externalStockPointId);

    /**
     * Get firstname
     * @return string|null
     */
    public function getFirstname();

    /**
     * Set firstname
     * @param string $firstname
     * @return \Riki\StockPoint\Api\Data\StockPointInterface
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
     * @return \Riki\StockPoint\Api\Data\StockPointInterface
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
     * @return \Riki\StockPoint\Api\Data\StockPointInterface
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
     * @return \Riki\StockPoint\Api\Data\StockPointInterface
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
     * @return \Riki\StockPoint\Api\Data\StockPointInterface
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
     * @return \Riki\StockPoint\Api\Data\StockPointInterface
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
     * @return \Riki\StockPoint\Api\Data\StockPointInterface
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
     * @return \Riki\StockPoint\Api\Data\StockPointInterface
     */
    public function setTelephone($telephone);
}
