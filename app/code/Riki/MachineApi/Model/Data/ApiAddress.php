<?php

namespace Riki\MachineApi\Model\Data;

class ApiAddress
    extends \Magento\Customer\Model\Data\Address
    implements \Riki\MachineApi\Api\Data\ApiAddressInterface
{
    /**
     * address last name
     * @return string
     */
    public function getCity(){
        return $this->_get(self::KEY_CITY);
    }
    /**
     * address last name
     * @return string
     */
    public function setCity($city){
        return $this->setData(self::KEY_CITY , $city);
    }
    /**
     * address last name
     * @return string
     */
    public function getAddressLastName(){
        return $this->_get(self::KEY_ADDRESS_LAST_NAME);
    }

    public function setAddressLastName($addressLastName){
        return $this->setData(self::KEY_ADDRESS_LAST_NAME , $addressLastName);
    }

    /**
     * address last name
     * @return string
     */
    public function getAddressFirstName(){
        return $this->_get(self::KEY_ADDRESS_FIRST_NAME);
    }

    public function setAddressFirstName($addressFirstName){
        return $this->setData(self::KEY_ADDRESS_FIRST_NAME , $addressFirstName);
    }

    /**
     * address last name
     * @return string
     */
    public function getAddressFirstNameKana(){
        return $this->_get(self::KEY_ADDRESS_FIRST_NAME_KANA);
    }

    public function setAddressFirstNameKana($addressFirstNameKana){
        return $this->setData(self::KEY_ADDRESS_FIRST_NAME_KANA , $addressFirstNameKana);
    }

    /**
     * address last name
     * @return string
     */
    public function getAddressLastNameKana(){
        return $this->_get(self::KEY_ADDRESS_LAST_NAME_KANA);
    }

    public function setAddressLastNameKana($addressLastNameKana){
        return $this->setData(self::KEY_ADDRESS_LAST_NAME_KANA , $addressLastNameKana);
    }

    /**
     * address last name
     * @return string
     */
    public function getPostalCode(){
        return $this->_get(self::KEY_POSTAL_CODE);
    }

    public function setPostalCode($postalCode){
        return $this->setData(self::KEY_POSTAL_CODE , $postalCode);
    }

    /**
     * address last name
     * @return string
     */
    public function getPrefectureCode(){
        return $this->_get(self::KEY_PREFECTURE_CODE);
    }

    public function setPrefectureCode($prefectureCode){
        return $this->setData(self::KEY_PREFECTURE_CODE , $prefectureCode);
    }

    /**
     * address last name
     * @return string
     */
    public function getAddress1(){
        return $this->_get(self::KEY_ADDRESS1);
    }

    public function setAddress1($address1){
        return $this->setData(self::KEY_ADDRESS1 , $address1);
    }

    /**
     * address last name
     * @return string
     */
    public function getAddress2(){
        return $this->_get(self::KEY_ADDRESS2);
    }

    public function setAddress2($address2){
        return $this->setData(self::KEY_ADDRESS2 , $address2);
    }

    /**
     * address last name
     * @return string
     */
    public function getPhoneNumber(){
        return $this->_get(self::KEY_PHONE_NUMBER);
    }

    public function setPhoneNumber($phoneNumber){
        return $this->setData(self::KEY_PHONE_NUMBER , $phoneNumber);
    }

    /**
     * address last name
     * @return string
     */
    public function getFaxNumber(){
        return $this->_get(self::KEY_FAX_NUMBER);
    }

    public function setFaxNumber($faxNumber){
        return $this->setData(self::KEY_FAX_NUMBER , $faxNumber);
    }

    public function convertToCustomerAddressObject(){
        $this->setFirstname($this->getAddressFirstName());
        $this->setLastname($this->getAddressLastName());
        $this->setStreet([
            $this->getAddress1() , $this->getAddress2()
        ]);
        $this->setCity($this->getCity());
        $this->setCountryId('JP');
        $this->setPostcode($this->getPostalCode());
        $this->setTelephone($this->getPhoneNumber());
        $this->setCustomAttribute('firstnamekana', $this->getAddressFirstNameKana());
        $this->setCustomAttribute('lastnamekana' , $this->getAddressLastNameKana());
        $this->setCustomAttribute('riki_nickname' , $this->getLastname() .  $this->getFirstname() );
        return $this;
    }
}