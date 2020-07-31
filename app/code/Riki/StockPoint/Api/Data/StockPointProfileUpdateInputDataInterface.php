<?php

namespace Riki\StockPoint\Api\Data;

interface StockPointProfileUpdateInputDataInterface 
{
  const PROFILE_ID = "profile_id";
  const STOCK_POINT = "stock_point";
  const NEXT_DELIVERY_DATE = "next_delivery_date";
  const NEXT_ORDER_DATE = "next_order_date";
  const DELIVERY_TIME = "delivery_time";
  const CURRENT_DISCOUNT_RATE = "current_discount_rate";
  const COMMENT_FOR_CUSTOMER = "comment_for_customer";
  const DELIVERY_TYPE = "delivery_type"; // locker/pickup/dropoff/subcarrier
  const BUCKET_ID = "bucket_id";
  /**
   * get profile ID
   * @return int
   */
   public function getProfileId(): int;
   
  /**
   * set profile ID
   * @param int $profileId
   * @return void
   */
   public function setProfileId($profileId);
   
  /**
   * get list of stockpoint input data interface
   * @return \Riki\StockPoint\Api\Data\ProfileUpdate\StockpointInputDataInterface
   */
   public function getStockPoint();
   
  /**
   * setter for stockpoint input interface
   * @param \Riki\StockPoint\Api\Data\ProfileUpdate\StockpointInputDataInterface $profileInputDataUpdateInterface
   * @return void
   */
   public function setStockPoint(
    \Riki\StockPoint\Api\Data\ProfileUpdate\StockpointInputDataInterface $profileInputDataUpdateInterface
   );
   
  /**
   * getter delivery date
   * @return string
   */
   public function getNextDeliveryDate(): string;
   
  /**
   * setter delivery date
   * @param string $nextDeliveryDate
   * @return void
   */
   public function setNextDeliveryDate($nextDeliveryDate);
   
  /**
   * getter next order date
   * @return string
   */
   public function getNextOrderDate(): string;
   
  /**
   * getter next delivery time
   * @return string
   */
   public function getDeliveryTime(): int;
   
  /**
   * getter next delivery time
   * @param int $deliveryTime
   * @return void
   */
   public function setDeliveryTime($deliveryTime);
   
  /**
   * setter next order date
   * @param string $nextOrderDate
   * @return void
   */ 
   public function setNextOrderDate($nextOrderDate);
   
  /**
   * setter discount rate
   * @return int
   */ 
   public function getCurrentDiscountRate(): int;
  
  /**
   * setter discount rate
   * @param string $discountRate
   * @return void
   */ 
   public function setCurrentDiscountRate($discountRate);
   
  /**
   * getter comment for customer
   * @return string
   */ 
   public function getCommentForCustomer(): string;
   
  /**
   * setter comment for customer
   * @param string $comment
   * @return void
   */
   public function setCommentForCustomer($comment);
  
  /**
   * getter delivery type
   * @return string
   */ 
   public function getDeliveryType(): string;
  
  /**
   * setter delivery type
   * @param string $deliveryType
   * @return void
   */ 
   public function setDeliveryType($deliveryType);
  
  /**
   * getter bucket ID
   * @return int
   */ 
   public function getBucketId(): int;
  
  /**
   * setter bucket id
   * @param int $bucketId
   * @return void
   */ 
   public function setBucketId($bucketId);
}
