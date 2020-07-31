<?php

namespace Riki\StockPoint\Api\Data\ProfileUpdate;

interface StockpointInputDataInterface 
{
    const STOCKPOINT_ID = "stock_point_id";
    const STOCKPOINT_POSTCODE = "stock_point_postcode";
    const STOCKPOINT_PREFECTURE = "stock_point_prefecture";
    const STOCKPOINT_ADDRESS = "stock_point_address";
    const STOCKPOINT_LASTNAME = "stock_point_lastname";
    const STOCKPOINT_FIRSTNAME = "stock_point_firstname";
    const STOCKPOINT_LASTNAME_KANA = "stock_point_lastnamekana";
    const STOCKPOINT_FIRSTNAME_KANA = "stock_point_firstnamekana";
    const STOCKPOINT_TELEPHONE = "stock_point_telephone";
    
   /**
    * getter stock point iD
    * @return int
    */
    public function getStockPointId(): int;
   
   /**
    * setter stock point iD
    * @param int $stockPointId
    * @return void
    */
    public function setStockPointId($stockPointId): void;
    
   /**
    * getter stockpoint postcode
    * @return string
    */
    public function getStockPointPostcode(): string;
  
   /**
    * setter stockpoint postcode
    * @param string $postcode
    * @return void
    */
    public function setStockPointPostcode($postcode): void; 
    
   /**
    * getter stockpoint prefecture
    * @return string
    */
    public function getStockPointPrefecture(): string;
    
   /**
    * setter stockpoint prefecture
    * @param string $prefectureCode
    * @return void
    */
    public function setStockPointPrefecture($prefectureCode): void;
   
   /**
    * getter stockpoint address
    * @return string
    */ 
    public function getStockPointAddress(): string;
   
   /**
    * setter stockpoint address
    * @param string $address
    * @return void
    */ 
    public function setStockPointAddress($address): void;
   
   /**
    * getter stockpoint lastname
    * @return string
    */ 
    public function getStockPointLastname(): string;
    
   /**
    * setter stockpoint lastname
    * @param string $stockpointLastname
    * @return void
    */
    public function setStockPointLastname($stockpointLastname): void;
    
   /**
    * getter stockpoint firstname
    * @return string
    */
    public function getStockPointFirstname(): string;
  
   /**
    * setter stockpoint firstname
    * @param string $stockpointFirstname
    * @return void
    */  
    public function setStockPointFirstname($stockpointFirstname): void;
    
   /**
    * setter stockpoint lastname kana
    * @param string $stockpointlastnamekana
    * @return void
    */ 
    public function setStockPointLastnamekana($stockpointlastnamekana): void;
    
   /**
    * getter stockpoint lastname kana
    * @return string
    */
    public function getStockPointLastnamekana(): string; 
    
   /**
    * setter stockpoint firstname kana
    * @param string $stockpointfirstnamekana
    * @return void
    */
    public function setStockPointFirstnamekana($stockpointfirstnamekana): void;
    
   /**
    * getter stockpoint firstname kana
    * @return string
    */ 
    public function getStockPointFirstnamekana(): string;
    
   /**
    * setter stockpoint telephone
    * @param string $stockpointtelephone
    * @return void
    */    
    public function setStockPointTelephone($stockpointtelephone): void;
    
   /**
    * getter stockpoint telephone
    * @return string
    */  
    public function getStockPointTelephone(): string;
    
}
