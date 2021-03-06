<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Riki\MachineApi\Model\Data;

use \Magento\Framework\Api\AttributeValueFactory;

/**
 * Class Customer
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class ApiCustomer extends \Magento\Framework\Api\AbstractExtensibleObject
                  implements \Riki\MachineApi\Api\Data\ApiCustomerInterface
{
    /**
     * @var \Magento\Customer\Api\CustomerMetadataInterface
     */
    protected $metadataService;

    /**
     * Initialize dependencies.
     *
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $attributeValueFactory
     * @param \Magento\Customer\Api\CustomerMetadataInterface $metadataService
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $attributeValueFactory,
        \Magento\Customer\Api\CustomerMetadataInterface $metadataService,
        $data = []
    ) {
        $this->metadataService = $metadataService;
        parent::__construct($extensionFactory, $attributeValueFactory, $data);
    }
    /**
     * @return string
     */
    public function getEmailType(){
        return $this->_get(self::KEY_CLIENT_MAIL_TYPE);
    }
    public function setEmailType($emailType){
        return $this->setData(self::KEY_CLIENT_MAIL_TYPE , $emailType);
    }

    public function getCompanyName(){
        $value = $this->_get(self::KEY_COMPANY_NAME);
        if(\Zend_Validate::is($value,'NotEmpty')){
            return $value;
        }else{
            return '';
        }
    }
    public function setCompanyName($companyName){
        return $this->setData(self::KEY_COMPANY_NAME , $companyName);
    }
    /**
     * {@inheritdoc}
     */
    public function getIntroducerEmail(){
        $value = $this->_get(self::INTRODUCER_E_MAIL);
        if(\Zend_Validate::is($value,'NotEmpty')){
            return $value;
        }else{
            return '';
        }
    }
    /**
     * {@inheritdoc}
     */
    public function setIntroducerEmail($introducerEmail){
        return $this->setData(self::INTRODUCER_E_MAIL , $introducerEmail);
    }
    /**
     * {@inheritdoc}
     */
    public function getEmployees(){
        $value = $this->_get(self::EMPLOYEES);
        if(\Zend_Validate::is($value,'NotEmpty')){
            return $value;
        }else{
            return '';
        }
    }
    /**
     * {@inheritdoc}
     */
    public function setEmployees($employees){
        $this->setData(self::EMPLOYEES , $employees);
    }
    /**
     * {@inheritdoc}
     */
    public function getAmbComPhNum(){
        $value = $this->_get(self::AMB_COM_PH_NUM);
        if(\Zend_Validate::is($value,'NotEmpty')){
            return $value;
        }else{
            return '';
        }
    }
    /**
     * {@inheritdoc}
     */
    public function setAmbComPhNum($comPhNum){
        return $this->setData(self::AMB_COM_PH_NUM , $comPhNum);
    }
    /**
     * {@inheritdoc}
     */
    public function getAmbChargePerson(){
        $value = $this->_get(self::AMB_CHARGE_PERSON);
        if(\Zend_Validate::is($value,'NotEmpty')){
            return $value;
        }else{
            return '';
        }
    }
    /**
     * {@inheritdoc}
     */
    public function setAmbChargePerson($chargePerson){
        return $this->setData(self::AMB_CHARGE_PERSON , $chargePerson);
    }
    /**
     * {@inheritdoc}
     */
    public function getAmbComDivisionName(){
        $value = $this->_get(self::AMB_CHARGE_PERSON);
        if(\Zend_Validate::is($value,'NotEmpty')){
            return $value;
        }else{
            return '';
        }
    }
    /**
     * {@inheritdoc}
     */
    public function setAmbComDivisionName($comDivisionName){
        return $this->setData(self::AMB_COM_DIVISION_NAME , $comDivisionName);
    }
    /**
     * {@inheritdoc}
     */
    public function getAmbComName(){
        $value = $this->_get(self::AMB_COM_NAME);
        if(\Zend_Validate::is($value,'NotEmpty')){
            return $value;
        }else{
            return '';
        }
    }
    /**
     * {@inheritdoc}
     */
    public function setAmbComName($comName){
        return $this->setData(self::AMB_COM_NAME , $comName);
    }
    /**
     * {@inheritdoc}
     */
    public function getNjlCharge(){
        $value = $this->_get(self::NJL_CHARGE);
        if(\Zend_Validate::is($value,'NotEmpty')){
            return $value;
        }else{
            return '';
        }
    }
    /**
     * {@inheritdoc}
     */
    public function setNjlCharge($njlCharge){
        return $this->setData(self::NJL_CHARGE , $njlCharge);
    }
    /**
     * {@inheritdoc}
     */
    public function getNjlChargeCompany(){
        $value = $this->_get(self::NJL_CHARGE);
        if(\Zend_Validate::is($value,'NotEmpty')){
            return $value;
        }else{
            return '';
        }
    }
    /**
     * {@inheritdoc}
     */
    public function setNjlChargeCompany($NjlChargeCompany){
        return $this->setData(self::NJL_CHARGE_COMPANY , $NjlChargeCompany );
    }
    /**
     * {@inheritdoc}
     */
    public function getAmbStopReason(){
        $value = $this->_get(self::AMB_STOP_REASON);
        if(\Zend_Validate::is($value,'NotEmpty')){
            return $value;
        }else{
            return '';
        }
    }
    /**
     * {@inheritdoc}
     */
    public function setAmbStopReason($ambStopReason){
        return $this->setData(self::AMB_STOP_REASON , $ambStopReason);
    }
    /**
     * {@inheritdoc}
     */
    public function getAmbStopDate(){
        $value = $this->_get(self::AMB_STOP_DATE);
        if(\Zend_Validate::is($value,'NotEmpty')){
            return $value;
        }else{
            return '';
        }
    }
    /**
     * {@inheritdoc}
     */
    public function setAmbStopDate($ambStopDate){
        return $this->setData(self::AMB_STOP_DATE , $ambStopDate);
    }
    /**
     * {@inheritdoc}
     */
    public function getRawAmbStopDate(){
        return $this->_get(self::AMB_STOP_DATE);
    }
    /**
     * {@inheritdoc}
     */
    public function getAmbApplicationDate(){
        $value = $this->_get(self::AMB_APPLICATION_DATE);
        if(\Zend_Validate::is($value,'NotEmpty')){
            return $value;
        }else{
            return '';
        }
    }
    /**
     * {@inheritdoc}
     */
    public function setAmbApplicationDate($ambApplicationDate){
        return $this->setData(self::AMB_APPLICATION_DATE , $ambApplicationDate);
    }
    /**
     * {@inheritdoc}
     */
    public function getRawAmbApplicationDate(){
        return $this->_get(self::AMB_APPLICATION_DATE);
    }
    /**
     * {@inheritdoc}
     */
    public function getConsumerPassword(){
        return $this->_get(self::KEY_PASSWORD);
    }
    /**
     * {@inheritdoc}
     */
    public function setConsumerPassword($consumerPassword){
        return $this->setData(self::KEY_PASSWORD , $consumerPassword);
    }
    /**
     * {@inheritdoc}
     */
    public function getAsstPhNum(){
        $value = $this->_get(self::KEY_ASST_PH_NUM);
        if(\Zend_Validate::is($value,'NotEmpty')){
            return $value;
        }else{
            return '';
        }
    }
    /**
     * {@inheritdoc}
     */
    public function setAsstPhNUm($asstPthNum){
        return $this->setData(self::KEY_ASST_PH_NUM , $asstPthNum);
    }
    /**
     * {@inheritdoc}
     */
    public function getWorkPhNum(){
        $value = $this->_get(self::KEY_WORK_PH_NUM);
        if(\Zend_Validate::is($value,'NotEmpty')){
            return $value;
        }else{
            return '';
        }
    }
    /**
     * {@inheritdoc}
     */
    public function setWorkPhNum($workPhNum){
        return $this->setData(self::KEY_WORK_PH_NUM , $workPhNum);
    }
    /**
     * {@inheritdoc}
     */
    public function getPostName(){
        $value = $this->_get(self::KEY_POST_NAME);
        if(\Zend_Validate::is($value,'NotEmpty')){
            return $value;
        }else{
            return '';
        }
    }
    /**
     * {@inheritdoc}
     */
    public function setPostName($postName){
        return $this->setData(self::KEY_POST_NAME , $postName);
    }
    /**
     * {@inheritdoc}
     */
    public function getCaution(){
        $value = $this->_get(self::KEY_CAUTION);
        if(\Zend_Validate::is($value,'NotEmpty')){
            return $value;
        }else{
            return '';
        }
    }
    /**
     * {@inheritdoc}
     */
    public function setCaution($caution){
        return $this->setData(self::KEY_CAUTION , $caution);
    }
    /**
     * {@inheritdoc}
     */
    public function getEpsFlg(){
        $value = $this->_get(self::KEY_EPS_FLG);
        if(\Zend_Validate::is($value,'NotEmpty')){
            return $value;
        }else{
            return '';
        }
    }
    /**
     * {@inheritdoc}
     */
    public function setEpsFlg($epsFlag){
        return $this->setData(self::KEY_EPS_FLG , $epsFlag);
    }
    /**
     * {@inheritdoc}
     */
    public function getJobTitle(){
        $value = $this->_get(self::KEY_JOB_TITLE);
        if(\Zend_Validate::is($value,'NotEmpty')){
            return $value;
        }else{
            return '';
        }
    }
    /**
     * {@inheritdoc}
     */
    public function setJobTitle($jobTitle){
        return $this->setData(self::KEY_JOB_TITLE , $jobTitle);
    }
    /**
     * {@inheritdoc}
     */
    public function getMaritalStatCode(){
        $value = $this->_get(self::KEY_MARITAL_STAT_CODE);
        if(\Zend_Validate::is($value,'NotEmpty')){
            return $value;
        }else{
            return '';
        }
    }
    /**
     * {@inheritdoc}
     */
    public function setMaritalStatCode($maritalStatCode){
        return $this->setData(self::KEY_MARITAL_STAT_CODE , $maritalStatCode);
    }
    /**
     * {@inheritdoc}
     */
    public function getEmail1Type(){
        $value = $this->_get(self::KEY_CLIENT_MAIL_TYPE1);
        if(\Zend_Validate::is($value,'NotEmpty')){
            return $value;
        }else{
            return '';
        }
    }
    /**
     * {@inheritdoc}
     */
    public function setEmail1Type($email1Type){
        return $this->setData(self::KEY_CLIENT_MAIL_TYPE1 , $email1Type);
    }


    /**
     * {@inheritdoc}
     */
    public function getBirthFlg(){
        return $this->_get(self::KEY_BIRTH_FLG);
    }
    /**
     * {@inheritdoc}
     */
    public function setBirthFlg($birthFlag){
        return $this->setData(self::KEY_BIRTH_FLG , $birthFlag);
    }

    /**
     * {@inheritdoc}
     */
    public function getEmail2Type(){
        return $this->_get(self::KEY_CLIENT_MAIL_TYPE2);
    }

    /**
     * {@inheritdoc}
     */
    public function setEmail2Type($email2Type){
        return $this->setData(self::KEY_CLIENT_MAIL_TYPE2 , $email2Type);
    }


    /**
     * {@inheritdoc}
     */
    public function getEmail2(){
        return $this->_get(self::KEY_EMAIL2);
    }

    /**
     * {@inheritdoc}
     */
    public function setEmail2($email2){
        return $this->setData(self::KEY_EMAIL2 , $email2);
    }

    /**
     * {@inheritdoc}
     */
    protected function getCustomAttributesCodes()
    {
        if ($this->customAttributesCodes === null) {
            $this->customAttributesCodes = $this->getEavAttributesCodes($this->metadataService);
        }
        return $this->customAttributesCodes;
    }

    /**
     * @return string|null
     */
    public function getDefaultBilling()
    {
        return $this->_get(self::DEFAULT_BILLING);
    }

    /**
     * Get default shipping address id
     *
     * @return string|null
     */
    public function getDefaultShipping()
    {
        return $this->_get(self::DEFAULT_SHIPPING);
    }

    /**
     * Get confirmation
     *
     * @return string|null
     */
    public function getConfirmation()
    {
        return $this->_get(self::CONFIRMATION);
    }

    /**
     * Get created at time
     *
     * @return string|null
     */
    public function getCreatedAt()
    {
        return $this->_get(self::CREATED_AT);
    }

    /**
     * Get created in area
     *
     * @return string|null
     */
    public function getCreatedIn()
    {
        return $this->_get(self::CREATED_IN);
    }

    /**
     * Get updated at time
     *
     * @return string|null
     */
    public function getUpdatedAt()
    {
        return $this->_get(self::UPDATED_AT);
    }

    /**
     * Get date of birth
     *
     * @return string|null
     */
    public function getDob()
    {
        return $this->_get(self::DOB);
    }

    /**
     * Get email address
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->_get(self::EMAIL);
    }

    /**
     * Get first name
     *
     * @return string
     */
    public function getFirstname()
    {
        return $this->_get(self::FIRSTNAME);
    }

    /**
     * Get gender
     *
     * @return string|null
     */
    public function getGender()
    {
        return $this->_get(self::GENDER);
    }

    /**
     * Get group id
     *
     * @return string|null
     */
    public function getGroupId()
    {
        return $this->_get(self::GROUP_ID);
    }

    /**
     * Get customer id
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->_get(self::ID);
    }

    /**
     * Get last name
     *
     * @return string
     */
    public function getLastname()
    {
        return $this->_get(self::LASTNAME);
    }

    /**
     * Get middle name
     *
     * @return string|null
     */
    public function getMiddlename()
    {
        return $this->_get(self::MIDDLENAME);
    }

    /**
     * Get prefix
     *
     * @return string|null
     */
    public function getPrefix()
    {
        return $this->_get(self::PREFIX);
    }

    /**
     * Get store id
     *
     * @return int|null
     */
    public function getStoreId()
    {
        return $this->_get(self::STORE_ID);
    }

    /**
     * Get suffix
     *
     * @return string|null
     */
    public function getSuffix()
    {
        return $this->_get(self::SUFFIX);
    }

    /**
     * Get tax Vat.
     *
     * @return string|null
     */
    public function getTaxvat()
    {
        return $this->_get(self::TAXVAT);
    }

    /**
     * Get website id
     *
     * @return int|null
     */
    public function getWebsiteId()
    {
        return $this->_get(self::WEBSITE_ID);
    }

    /**
     * Get addresses
     *
     * @return \Magento\Customer\Api\Data\AddressInterface[]|null
     */
    public function getAddresses()
    {
        return $this->_get(self::KEY_ADDRESSES);
    }

    /**
     * Get disable auto group change flag.
     *
     * @return int|null
     */
    public function getDisableAutoGroupChange()
    {
        return $this->_get(self::DISABLE_AUTO_GROUP_CHANGE);
    }

    /**
     * Set customer id
     *
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * Set group id
     *
     * @param int $groupId
     * @return $this
     */
    public function setGroupId($groupId)
    {
        return $this->setData(self::GROUP_ID, $groupId);
    }

    /**
     * Set default billing address id
     *
     * @param string $defaultBilling
     * @return $this
     */
    public function setDefaultBilling($defaultBilling)
    {
        return $this->setData(self::DEFAULT_BILLING, $defaultBilling);
    }

    /**
     * Set default shipping address id
     *
     * @param string $defaultShipping
     * @return $this
     */
    public function setDefaultShipping($defaultShipping)
    {
        return $this->setData(self::DEFAULT_SHIPPING, $defaultShipping);
    }

    /**
     * Set confirmation
     *
     * @param string $confirmation
     * @return $this
     */
    public function setConfirmation($confirmation)
    {
        return $this->setData(self::CONFIRMATION, $confirmation);
    }

    /**
     * Set created at time
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * Set updated at time
     *
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * Set created in area
     *
     * @param string $createdIn
     * @return $this
     */
    public function setCreatedIn($createdIn)
    {
        return $this->setData(self::CREATED_IN, $createdIn);
    }

    /**
     * Set date of birth
     *
     * @param string $dob
     * @return $this
     */
    public function setDob($dob)
    {
        return $this->setData(self::DOB, $dob);
    }

    /**
     * Set email address
     *
     * @param string $email
     * @return $this
     */
    public function setEmail($email)
    {
        return $this->setData(self::EMAIL, $email);
    }

    /**
     * Set first name
     *
     * @param string $firstname
     * @return $this
     */
    public function setFirstname($firstname)
    {
        return $this->setData(self::FIRSTNAME, $firstname);
    }

    /**
     * Set last name
     *
     * @param string $lastname
     * @return string
     */
    public function setLastname($lastname)
    {
        return $this->setData(self::LASTNAME, $lastname);
    }

    /**
     * Set middle name
     *
     * @param string $middlename
     * @return $this
     */
    public function setMiddlename($middlename)
    {
        return $this->setData(self::MIDDLENAME, $middlename);
    }

    /**
     * Set prefix
     *
     * @param string $prefix
     * @return $this
     */
    public function setPrefix($prefix)
    {
        return $this->setData(self::PREFIX, $prefix);
    }

    /**
     * Set suffix
     *
     * @param string $suffix
     * @return $this
     */
    public function setSuffix($suffix)
    {
        return $this->setData(self::SUFFIX, $suffix);
    }

    /**
     * Set gender
     *
     * @param string $gender
     * @return $this
     */
    public function setGender($gender)
    {
        return $this->setData(self::GENDER, $gender);
    }

    /**
     * Set store id
     *
     * @param int $storeId
     * @return $this
     */
    public function setStoreId($storeId)
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    /**
     * Set tax Vat
     *
     * @param string $taxvat
     * @return $this
     */
    public function setTaxvat($taxvat)
    {
        return $this->setData(self::TAXVAT, $taxvat);
    }

    /**
     * Set website id
     *
     * @param int $websiteId
     * @return $this
     */
    public function setWebsiteId($websiteId)
    {
        return $this->setData(self::WEBSITE_ID, $websiteId);
    }

    /**
     * Set customer addresses.
     *
     * @param \Magento\Customer\Api\Data\AddressInterface[] $addresses
     * @return $this
     */
    public function setAddresses(array $addresses = null)
    {
        return $this->setData(self::KEY_ADDRESSES, $addresses);
    }

    /**
     * Set disable auto group change flag.
     *
     * @param int $disableAutoGroupChange
     * @return $this
     */
    public function setDisableAutoGroupChange($disableAutoGroupChange)
    {
        return $this->setData(self::DISABLE_AUTO_GROUP_CHANGE, $disableAutoGroupChange);
    }

    /**
     * {@inheritdoc}
     *
     * @return \Magento\Customer\Api\Data\CustomerExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * {@inheritdoc}
     *
     * @param \Magento\Customer\Api\Data\CustomerExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(\Magento\Customer\Api\Data\CustomerExtensionInterface $extensionAttributes)
    {
        return $this->_setExtensionAttributes($extensionAttributes);
    }


    /**
     * Get cart id
     *
     * @return int|null
     */
    public function getCartId()
    {
        return $this->_get(self::CART_ID);
    }
    /**
     * Set cart id
     *
     * @param int $cartId
     * @return $this
     */
    public function setCartId($cartId)
    {
        return $this->setData(self::CART_ID, $cartId);
    }







}
