<?php


namespace Riki\StockPoint\Model;

use Riki\StockPoint\Api\Data\StockPointInterface;

class StockPoint extends \Magento\Framework\Model\AbstractModel implements StockPointInterface
{

    protected $_eventPrefix = 'stock_point';

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Riki\StockPoint\Model\ResourceModel\StockPoint::class);
    }

    /**
     * Get stock_point_id
     * @return string
     */
    public function getStockPointId()
    {
        return $this->getData(self::STOCK_POINT_ID);
    }

    /**
     * Set stock_point_id
     * @param string $stockPointId
     * @return \Riki\StockPoint\Api\Data\StockPointInterface
     */
    public function setStockPointId($stockPointId)
    {
        return $this->setData(self::STOCK_POINT_ID, $stockPointId);
    }

    /**
     * Get external_stock_point_id
     * @return string
     */
    public function getExternalStockPointId()
    {
        return $this->getData(self::EXTERNAL_STOCK_POINT_ID);
    }

    /**
     * Set external_stock_point_id
     * @param string $externalStockPointId
     * @return \Riki\StockPoint\Api\Data\StockPointInterface
     */
    public function setExternalStockPointId($externalStockPointId)
    {
        return $this->setData(self::EXTERNAL_STOCK_POINT_ID, $externalStockPointId);
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
     * @return \Riki\StockPoint\Api\Data\StockPointInterface
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
     * @return \Riki\StockPoint\Api\Data\StockPointInterface
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
     * @return \Riki\StockPoint\Api\Data\StockPointInterface
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
     * @return \Riki\StockPoint\Api\Data\StockPointInterface
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
     * @return \Riki\StockPoint\Api\Data\StockPointInterface
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
     * @return \Riki\StockPoint\Api\Data\StockPointInterface
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
     * @return \Riki\StockPoint\Api\Data\StockPointInterface
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
     * @return \Riki\StockPoint\Api\Data\StockPointInterface
     */
    public function setTelephone($telephone)
    {
        return $this->setData(self::TELEPHONE, $telephone);
    }
}
