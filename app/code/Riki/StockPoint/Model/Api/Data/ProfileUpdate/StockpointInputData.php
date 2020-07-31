<?php

namespace Riki\StockPoint\Model\Api\Data\ProfileUpdate;

class StockpointInputData
 extends \Magento\Framework\Api\AbstractExtensibleObject
  implements \Riki\StockPoint\Api\Data\ProfileUpdate\StockpointInputDataInterface
{
   /**
    * {@inheritdoc}
    */
    public function getStockPointId(): int{
       return $this->_get(self::STOCKPOINT_ID);
    }
   
   /**
    * {@inheritdoc}
    */
    public function setStockPointId($stockPointId): void {
       $this->setData(self::STOCKPOINT_ID, $stockPointId);
    }
    
   /**
    * {@inheritdoc}
    */
    public function getStockPointPostcode(): string {
       return $this->_get(self::STOCKPOINT_POSTCODE);
    }
  
   /**
    * {@inheritdoc}
    */
    public function setStockPointPostcode($postcode): void {
       $this->setData(self::STOCKPOINT_POSTCODE, $postcode);
    }
    
   /**
    * {@inheritdoc}
    */
    public function getStockPointPrefecture(): string {
       return $this->_get(self::STOCKPOINT_PREFECTURE);
    }
    
   /**
    * {@inheritdoc}
    */
    public function setStockPointPrefecture($prefectureCode): void {
       $this->setData(self::STOCKPOINT_PREFECTURE, $prefectureCode);
    }
   
   /**
    * {@inheritdoc}
    */
    public function getStockPointAddress(): string {
       return $this->_get(self::STOCKPOINT_ADDRESS);
    }
   
   /**
    * {@inheritdoc}
    */
    public function setStockPointAddress($address): void {
       $this->setData(self::STOCKPOINT_ADDRESS, $address);
    }
   
   /**
    * {@inheritdoc}
    */
    public function getStockPointLastname(): string {
       return $this->_get(self::STOCKPOINT_LASTNAME);
    }
    
   /**
    * {@inheritdoc}
    */
    public function setStockPointLastname($stockpointLastname): void {
       $this->setData(self::STOCKPOINT_LASTNAME, $stockpointLastname);
    }
    
   /**
    * {@inheritdoc}
    */
    public function getStockPointFirstname(): string {
       return $this->_get(self::STOCKPOINT_FIRSTNAME);
    }
  
   /**
    * {@inheritdoc}
    */  
    public function setStockPointFirstname($stockpointFirstname): void {
      $this->setData(self::STOCKPOINT_FIRSTNAME, $stockpointFirstname);
    }
    
   /**
    * {@inheritdoc}
    */ 
    public function setStockPointLastnamekana($stockpointlastnamekana): void {
      $this->setData(self::STOCKPOINT_LASTNAME_KANA, $stockpointlastnamekana);
    }
    
   /**
    * {@inheritdoc}
    */
    public function getStockPointLastnamekana(): string {
      return $this->_get(self::STOCKPOINT_LASTNAME_KANA);
    }
    
   /**
    * {@inheritdoc}
    */
    public function setStockPointFirstnamekana($stockpointfirstnamekana): void {
       $this->setData(self::STOCKPOINT_FIRSTNAME_KANA, $stockpointfirstnamekana);
    }
    
   /**
    * {@inheritdoc}
    */ 
    public function getStockPointFirstnamekana(): string {
       return $this->_get(self::STOCKPOINT_FIRSTNAME_KANA);
    }
    
   /**
    * {@inheritdoc}
    */    
    public function setStockPointTelephone($stockpointtelephone): void {
        $this->setData(self::STOCKPOINT_TELEPHONE, $stockpointtelephone);
    }
    
   /**
    * {@inheritdoc}
    */  
    public function getStockPointTelephone(): string {
       return $this->_get(self::STOCKPOINT_TELEPHONE);
    }
}
