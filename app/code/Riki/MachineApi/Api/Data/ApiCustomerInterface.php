<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\MachineApi\Api\Data;

/**
 * Customer interface.
 */
interface ApiCustomerInterface
    extends \Magento\Framework\Api\CustomAttributesDataInterface
{
    /**#@+
     * Constants defined for keys of the data array. Identical to the name of the getter in snake case
     */

    const ID = 'id';
    const CONFIRMATION = 'confirmation';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const CREATED_IN = 'created_in';
    const DOB = 'dob';
    const EMAIL = 'email';
    const FIRSTNAME = 'firstname';
    const GENDER = 'gender';
    const GROUP_ID = 'group_id';
    const LASTNAME = 'lastname';
    const MIDDLENAME = 'middlename';
    const PREFIX = 'prefix';
    const STORE_ID = 'store_id';
    const SUFFIX = 'suffix';
    const TAXVAT = 'taxvat';
    const WEBSITE_ID = 'website_id';
    const DEFAULT_BILLING = 'default_billing';
    const DEFAULT_SHIPPING = 'default_shipping';
    const KEY_ADDRESSES = 'addresses';
    const DISABLE_AUTO_GROUP_CHANGE = 'disable_auto_group_change';
    const CART_ID = 'cart_id';

    /* consumer db key */
    const KEY_SEX = 'gender';
    const KEY_BIRTH_DATE = 'dob';
    const KEY_BIRTH_FLG = 'birth_flg';
    const KEY_MARITAL_STAT_CODE = 'marital_stat_code';
    const KEY_CLIENT_MAIL_TYPE = 'email_type';
    const KEY_CLIENT_MAIL_TYPE2 = 'email_2_type';
    const KEY_EMAIL2 = 'email_2';
    const KEY_CLIENT_MAIL_TYPE1 = 'email_1_type';
    const KEY_JOB_TITLE = 'job_title';
    const KEY_EPS_FLG = 'eps_flg'; // 1:unacceptable 0:accept
    const KEY_CAUTION = 'caution'; // text area
    const KEY_COMPANY_NAME = 'company_name'; // text area
    const KEY_POST_NAME = 'post_name'; // text area
    const KEY_WORK_PH_NUM = 'work_ph_num'; // text area
    const KEY_ASST_PH_NUM = 'asst_ph_num'; // text area
    const KEY_PASSWORD = 'consumer_password'; //
    const AMB_APPLICATION_DATE = 'amb_application_date'; // date time
    const AMB_STOP_DATE = 'amb_stop_date'; // date time
    const AMB_STOP_REASON = 'amb_stop_reason'; // text
    const NJL_CHARGE_COMPANY = 'njl_charge_company'; // text
    const NJL_CHARGE = 'njl_charge'; // text
    const AMB_COM_NAME = 'amb_com_name'; // text
    const AMB_COM_DIVISION_NAME = 'amb_com_division_name'; //
    const AMB_CHARGE_PERSON = 'amb_charge_person';
    const AMB_COM_PH_NUM = 'amb_com_ph_num';
    const EMPLOYEES = 'employees';
    const INTRODUCER_E_MAIL = 'introducer_email' ;



    /* consumer db function */
    /**
     * @return string
     */
    public function getEmailType();
    public function setEmailType($emailType);
    /**
     * @return string
     */
    public function getIntroducerEmail();
    public function setIntroducerEmail($introducerEmail);

    /**
     * @return string
     */
    public function getEmployees();
    public function setEmployees($employees);

    /**
     * @return string
     */
    public function getAmbComPhNum();
    public function setAmbComPhNum($comPhNum);

    /**
     * @return string
     */
    public function getAmbChargePerson();
    public function setAmbChargePerson($chargePerson);

    /**
     * @return string
     */
    public function getAmbComDivisionName();
    public function setAmbComDivisionName($comDivisionName);

    /**
     * @return string
     */
    public function getAmbComName();
    public function setAmbComName($comName);

    /**
     * @return string
     */
    public function getNjlCharge();
    public function setNjlCharge($njlCharge);

    /**
     * @return string
     */
    public function getNjlChargeCompany();
    public function setNjlChargeCompany($NjlChargeCompany);

    /**
     * @return string
     */
    public function getAmbStopReason();
    public function setAmbStopReason($ambStopReason);

    /**
     * @return string
     */
    public function getAmbStopDate();
    public function setAmbStopDate($ambStopDate);
    /**
     * @return string
     */
    public function getRawAmbStopDate();

    /**
     * @return string
     */
    public function getAmbApplicationDate();
    public function setAmbApplicationDate($ambApplicationDate);
    /**
     * @return string
     */
    public function getRawAmbApplicationDate();

    /**
     * @return string
     */
    public function getConsumerPassword();
    public function setConsumerPassword($consumerPassword);

    /**
     * @return string
     */
    public function getAsstPhNum();
    public function setAsstPhNUm($asstPthNum);

    /**
     * @return string
     */
    public function getWorkPhNum();
    public function setWorkPhNum($workPhNum);

    /**
     * @return string
     */
    public function getPostName();
    public function setPostName($postName);

    /**
     * @return string
     */
    public function getCompanyName();
    public function setCompanyName($companyName);

    /**
     * @return string
     */
    public function getCaution();
    public function setCaution($caution);

    /**
     * @return string
     */
    public function getEpsFlg();
    public function setEpsFlg($epsFlag);

    /**
     * @return string
     */
    public function getJobTitle();
    public function setJobTitle($jobTitle);

    /**
     * @return string
     */
    public function getMaritalStatCode();
    public function setMaritalStatCode($maritalStatCode);

    /**
     * @return string
     */
    public function getEmail1Type();
    public function setEmail1Type($email1Type);
    /**
     *  birth day flag
     * @return string
     */
    public function getBirthFlg();

    public function setBirthFlg($birthFlag);

    /**
     *  get email 2 type
     * @return string
     */
    public function getEmail2Type();

    public function setEmail2Type($email2Type);


    /**
     *  get email 2
     * @return string
     */
    public function getEmail2();

    public function setEmail2($email2);


    /**
     * Get customer addresses.
     *
     * @api
     * @return \Riki\MachineApi\Api\Data\ApiAddressInterface[]|null
     */
    public function getAddresses();

    /**
     * Set customer addresses.
     *
     * @api
     * @param \Riki\MachineApi\Api\Data\ApiAddressInterface[] $addresses
     * @return $this
     */
    public function setAddresses(array $addresses = null);

    /* end consumer db function */


    /**#@-*/
    /**
     * Get cart id
     *
     * @api
     * @return int|null
     */
    public function getCartId();

    /**
     * Set cart id
     *
     * @api
     * @param int $cartId
     * @return $this
     */
    public function setCartId($cartId);



    /**
     * Get customer id
     *
     * @api
     * @return int|null
     */
    public function getId();

    /**
     * Set customer id
     *
     * @api
     * @param int $id
     * @return $this
     */
    public function setId($id);

    /**
     * Get group id
     *
     * @api
     * @return int|null
     */
    public function getGroupId();

    /**
     * Set group id
     *
     * @api
     * @param int $groupId
     * @return $this
     */
    public function setGroupId($groupId);

    /**
     * Get default billing address id
     *
     * @api
     * @return string|null
     */
    public function getDefaultBilling();

    /**
     * Set default billing address id
     *
     * @api
     * @param string $defaultBilling
     * @return $this
     */
    public function setDefaultBilling($defaultBilling);

    /**
     * Get default shipping address id
     *
     * @api
     * @return string|null
     */
    public function getDefaultShipping();

    /**
     * Set default shipping address id
     *
     * @api
     * @param string $defaultShipping
     * @return $this
     */
    public function setDefaultShipping($defaultShipping);

    /**
     * Get confirmation
     *
     * @api
     * @return string|null
     */
    public function getConfirmation();

    /**
     * Set confirmation
     *
     * @api
     * @param string $confirmation
     * @return $this
     */
    public function setConfirmation($confirmation);

    /**
     * Get created at time
     *
     * @api
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set created at time
     *
     * @api
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt);

    /**
     * Get updated at time
     *
     * @api
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * Set updated at time
     *
     * @api
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt);

    /**
     * Get created in area
     *
     * @api
     * @return string|null
     */
    public function getCreatedIn();

    /**
     * Set created in area
     *
     * @api
     * @param string $createdIn
     * @return $this
     */
    public function setCreatedIn($createdIn);

    /**
     * Get date of birth
     *
     * @api
     * @return string|null
     */
    public function getDob();

    /**
     * Set date of birth
     *
     * @api
     * @param string $dob
     * @return $this
     */
    public function setDob($dob);

    /**
     * Get email address
     *
     * @api
     * @return string
     */
    public function getEmail();

    /**
     * Set email address
     *
     * @api
     * @param string $email
     * @return $this
     */
    public function setEmail($email);

    /**
     * Get first name
     *
     * @api
     * @return string
     */
    public function getFirstname();

    /**
     * Set first name
     *
     * @api
     * @param string $firstname
     * @return $this
     */
    public function setFirstname($firstname);

    /**
     * Get last name
     *
     * @api
     * @return string
     */
    public function getLastname();

    /**
     * Set last name
     *
     * @api
     * @param string $lastname
     * @return $this
     */
    public function setLastname($lastname);

    /**
     * Get middle name
     *
     * @api
     * @return string|null
     */
    public function getMiddlename();

    /**
     * Set middle name
     *
     * @api
     * @param string $middlename
     * @return $this
     */
    public function setMiddlename($middlename);

    /**
     * Get prefix
     *
     * @api
     * @return string|null
     */
    public function getPrefix();

    /**
     * Set prefix
     *
     * @api
     * @param string $prefix
     * @return $this
     */
    public function setPrefix($prefix);

    /**
     * Get suffix
     *
     * @api
     * @return string|null
     */
    public function getSuffix();

    /**
     * Set suffix
     *
     * @api
     * @param string $suffix
     * @return $this
     */
    public function setSuffix($suffix);

    /**
     * Get gender
     *
     * @api
     * @return int|null
     */
    public function getGender();

    /**
     * Set gender
     *
     * @api
     * @param int $gender
     * @return $this
     */
    public function setGender($gender);

    /**
     * Get store id
     *
     * @api
     * @return int|null
     */
    public function getStoreId();

    /**
     * Set store id
     *
     * @api
     * @param int $storeId
     * @return $this
     */
    public function setStoreId($storeId);

    /**
     * Get tax Vat
     *
     * @api
     * @return string|null
     */
    public function getTaxvat();

    /**
     * Set tax Vat
     *
     * @api
     * @param string $taxvat
     * @return $this
     */
    public function setTaxvat($taxvat);

    /**
     * Get website id
     *
     * @api
     * @return int|null
     */
    public function getWebsiteId();

    /**
     * Set website id
     *
     * @api
     * @param int $websiteId
     * @return $this
     */
    public function setWebsiteId($websiteId);

    /**
     * Get disable auto group change flag.
     *
     * @api
     * @return int|null
     */
    public function getDisableAutoGroupChange();

    /**
     * Set disable auto group change flag.
     *
     * @api
     * @param int $disableAutoGroupChange
     * @return $this
     */
    public function setDisableAutoGroupChange($disableAutoGroupChange);

    /**
     * Retrieve existing extension attributes object or create a new one.
     *
     * @api
     * @return \Magento\Customer\Api\Data\CustomerExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     *
     * @api
     * @param \Magento\Customer\Api\Data\CustomerExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(\Magento\Customer\Api\Data\CustomerExtensionInterface $extensionAttributes);
}
