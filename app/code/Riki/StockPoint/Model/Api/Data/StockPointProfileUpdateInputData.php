<?php

namespace Riki\StockPoint\Model\Api\Data;

class StockPointProfileUpdateInputData 
   extends \Magento\Framework\Api\AbstractExtensibleObject
    implements \Riki\StockPoint\Api\Data\StockPointProfileUpdateInputDataInterface
{
  /**
   * {@inheritdoc}
   */
   public function getProfileId(): int {
      return $this->_get(self::PROFILE_ID);
   }
   
  /**
   * {@inheritdoc}
   */
   public function setProfileId($profileId) {
      $this->setData(self::PROFILE_ID, $profileId);
   }
   
  /**
   * {@inheritdoc}
   */
   public function getStockPoint() {
       return $this->_get(self::STOCK_POINT);
   }
   
  /**
   * {@inheritdoc}
   */
   public function setStockPoint(
       \Riki\StockPoint\Api\Data\ProfileUpdate\StockpointInputDataInterface $profileInputDataUpdateInterface
   ) {
       $this->setData(self::STOCK_POINT, $profileInputDataUpdateInterface);
   }
   
  /**
   * {@inheritdoc}
   */
   public function getNextDeliveryDate(): string {
       return $this->_get(self::NEXT_DELIVERY_DATE);
   }
   
  /**
   * {@inheritdoc}
   */
   public function setNextDeliveryDate($nextDeliveryDate) {
       $this->setData(self::NEXT_DELIVERY_DATE, $nextDeliveryDate);
   }
   
  /**
   * {@inheritdoc}
   */
   public function getNextOrderDate(): string {
       return $this->_get(self::NEXT_ORDER_DATE);
   }
   
  /**
   * {@inheritdoc}
   */ 
   public function setNextOrderDate($nextOrderDate) {
       $this->setData(self::NEXT_ORDER_DATE, $nextOrderDate);
   }
   
  /**
   * {@inheritdoc}
   */
   public function getDeliveryTime(): int {
      $value = $this->_get(self::DELIVERY_TIME);
      return \Zend_Validate::is($value,"NotEmpty")?intval($value):-1;
   }
   
  /**
   * {@inheritdoc}
   */
   public function setDeliveryTime($deliveryTime) {
      $this->setData(self::DELIVERY_TIME, $deliveryTime);
   }
   
  /**
   * {@inheritdoc}
   */ 
   public function getCurrentDiscountRate(): int {
       return $this->_get(self::CURRENT_DISCOUNT_RATE);
   }
  
  /**
   * {@inheritdoc}
   */ 
   public function setCurrentDiscountRate($discountRate) {
       $this->setData(self::CURRENT_DISCOUNT_RATE, $discountRate);
   }
   
  /**
   * {@inheritdoc}
   */ 
   public function getCommentForCustomer(): string {
       return $this->_get(self::COMMENT_FOR_CUSTOMER);
   }
   
  /**
   * {@inheritdoc}
   */
   public function setCommentForCustomer($comment) {
       $this->setData(self::COMMENT_FOR_CUSTOMER, $comment);
   }
  
  /**
   * {@inheritdoc}
   */ 
   public function getDeliveryType(): string {
       return $this->_get(self::DELIVERY_TYPE);
   }
  
  /**
   * {@inheritdoc}
   */ 
   public function setDeliveryType($deliveryType) {
        $this->setData(self::DELIVERY_TYPE, $deliveryType);
   }
  
  /**
   * {@inheritdoc}
   */ 
   public function getBucketId(): int {
      return $this->_get(self::BUCKET_ID); 
   }
  
  /**
   * {@inheritdoc}
   */ 
   public function setBucketId($bucketId) {
       $this->setData(self::BUCKET_ID, $bucketId);
   }
   
}
