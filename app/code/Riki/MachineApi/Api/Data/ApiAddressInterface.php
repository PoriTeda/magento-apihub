<?php

namespace Riki\MachineApi\Api\Data;

interface ApiAddressInterface
    extends \Magento\Customer\Api\Data\AddressInterface
{
    const KEY_ADDRESS_LAST_NAME = 'address_last_name';
    const KEY_ADDRESS_FIRST_NAME = 'address_first_name';
    const KEY_ADDRESS_LAST_NAME_KANA = 'address_last_name_kana';
    const KEY_ADDRESS_FIRST_NAME_KANA = 'address_first_name_kana';
    const KEY_POSTAL_CODE = 'postal_code';
    const KEY_PREFECTURE_CODE = 'prefecture_code';
    const KEY_ADDRESS1 = 'address_1';
    const KEY_ADDRESS2 = 'address_2';
    const KEY_PHONE_NUMBER = 'phone_number';
    const KEY_FAX_NUMBER = 'fax_number';
    const KEY_CITY = 'city';
    const KEY_REGION = 'region';

    /**
     * city
     * @return string
     */
    public function getCity();
    public function setCity($city);


    /**
     * address last name
     * @return string
     */
    public function getAddressLastName();

    public function setAddressLastName($addressLastName);

    /**
     * address last name
     * @return string
     */
    public function getAddressFirstName();

    public function setAddressFirstName($addressFirstName);

    /**
     * address last name
     * @return string
     */
    public function getAddressFirstNameKana();

    public function setAddressFirstNameKana($addressFirstNameKana);

    /**
     * address last name
     * @return string
     */
    public function getAddressLastNameKana();

    public function setAddressLastNameKana($addressLastNameKana);

    /**
     * address last name
     * @return string
     */
    public function getPostalCode();

    public function setPostalCode($postalCode);

    /**
     * address last name
     * @return string
     */
    public function getPrefectureCode();

    public function setPrefectureCode($prefectureCode);

    /**
     * address last name
     * @return string
     */
    public function getAddress1();

    public function setAddress1($address1);

    /**
     * address last name
     * @return string
     */
    public function getAddress2();

    public function setAddress2($address2);

    /**
     * address last name
     * @return string
     */
    public function getPhoneNumber();

    public function setPhoneNumber($phoneNumber);

    /**
     * address last name
     * @return string
     */
    public function getFaxNumber();

    public function setFaxNumber($faxNumber);

}