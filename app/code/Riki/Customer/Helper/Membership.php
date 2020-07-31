<?php
namespace Riki\Customer\Helper;

use Magento\Framework\Exception\LocalizedException;

class Membership extends \Magento\Framework\App\Helper\AbstractHelper
{
    const MEMBERSHIP_CIS_CODE = 'cis';
    const MEMBERSHIP_CNC_CODE = 'cnc';
    const MEMBERSHIP_NCS_CODE = 'ncs';

    protected $_mappingAttributeSubProfile = [
        'CNC_Status' => 1133,
        'CIS_Status'=> 1134,
        'MILANO_STATUS'=> 1135,
        'ALLEGRIA_STATUS'=> 1136,
        'SUBSCRIPTION_STATUS'=> 1131,
        'AMB_TYPE'=> 770,
        'PA_CUSTOMER_TYPE'=> 880,
        'NWC_CAT_STATUS'=> 960,
        'NWC_CUSTOMER_STATUS'=> 890,
        'WELLNESSCLUB_AMB' => 1202,
        'BUSINESS_CODE' =>720,
        'SATELLITE_FLG' => 970,
        'CHOCOLLATORY_FLG' => 980,
        'KITKAT_CLUB_FLG' => 990,
        'LENDING_STATUS_NBA'=>915,
        'LENDING_STATUS_NDG'=>916,
        'LENDING_STATUS_SPT'=>917,
        'LENDING_STATUS_ICS'=>918,
        'LENDING_STATUS_NSP'=>919,
        'LENDING_STATUS_DUO' => 2570,
        'AMB_FRIENDS' => 1200,
        'SATELLITE_AMB' =>1201,
        'NescafeStandFlg'=>1850
    ];

    /**
     * @var $_mappingCustomer
     */
    protected $_mappingCustomer = array();

    /**
     * @var $_aCustomerData
     */
    protected $_aCustomerData;

    /**
     * @var $_aSubProfileData
     */
    protected $_aSubProfileData;
    /**
     * @var \Magento\Store\Api\WebsiteRepositoryInterface
     */
    protected $_websiteRepositoryInterface;
    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    protected $_eavConfig;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Riki\SubscriptionMembership\Model\Customer\Attribute\Source\Membership $customerMembership,
        \Magento\Store\Api\WebsiteRepositoryInterface $websiteRepositoryInterface,
        \Magento\Eav\Model\Config $eavConfig
    )
    {
        $this->_customerMembership = $customerMembership;
        $this->_websiteRepositoryInterface = $websiteRepositoryInterface;
        $this->_storeManager = $context->getStoreManager();
        $this->_eavConfig = $eavConfig;
    }

    /**
     * GetOptionMemberShip
     */
    public function initMappingFieldCustomer($aCustomerData,$aSubProfileData){

        //website
        $this->_mappingCustomer['EC Site'] = 1;
        $this->_mappingCustomer['Employee Site'] = 2;
        $this->_mappingCustomer['CNC Site'] = 3;
        $this->_mappingCustomer['CIS Site'] = 4;
        $this->_mappingCustomer['Milano Site'] = 5;
        $this->_mappingCustomer['Alegria Site'] = 6;
        $this->_mappingCustomer['NescafeStand site'] = 7;

        //group
        $this->_mappingCustomer['Normal EC'] = 1;
        $this->_mappingCustomer['Subscriber'] = 2;
        $this->_mappingCustomer['Club Member'] = 3;


        //membership
        $memberships = $this->_customerMembership->getAllOptions();

        foreach($memberships as $membership){
            $this->_mappingCustomer[$membership['label']->getText()] = $membership['value'];
        }

        $this->_aCustomerData   =   $aCustomerData;
        $this->_aSubProfileData   =   $aSubProfileData;

    }



    /**
     * FindMappingCustomer
     *
     * @param $label
     * @return mixed
     * @throws \Exception
     */
    public function findMappingCustomer($label){
        if(!isset($this->_mappingCustomer[$label])){
            throw new LocalizedException(__('Missing id of membership %1', $label));
        }
        return $this->_mappingCustomer[$label];
    }


    /**
     * getCustomerWebsite
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getCustomerWebsite(){
        $sites = array('EC Site');

        if($this->checkExistKeyAndValue('CNC_Status',1)){
            $sites[] = 'CNC Site';
        }
        if($this->checkExistKeyAndValue('CIS_Status',1)){
            $sites[] = 'CIS Site';
        }
        if($this->checkExistKeyAndValue('MILANO_STATUS',1)){
            $sites[] = 'Milano Site';
        }
        if($this->checkExistKeyAndValue('ALLEGRIA_STATUS',1)){
            $sites[] = 'Alegria Site';
        }

        if( $this->checkExistKeyAndValueCustomer('EMP_FLG',1)){
            $sites[] = 'Employee Site';
        }

        if ($this->checkExistKeyAndValue('NescafeStandFlg', 1)) {
            $sites[] = 'NescafeStand site';
        }

        $sitesId = array();
        foreach($sites as $site){
            $sitesId[] = $this->findMappingCustomer($site);
        }
        return $sitesId;
    }

    /**
     * getCustomerGroup
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getCustomerGroup(){

        $group = 'Normal EC';

        //check for customer group

        if(
            ($this->checkExistKeyAndValue('SUBSCRIPTION_STATUS',0) ||  $this->checkExistKeyAndValue('SUBSCRIPTION_STATUS',0,true))
            && !$this->checkExistKeyAndValue('NWC_CAT_STATUS',4)
            && !$this->checkExistKeyAndValue('NWC_CUSTOMER_STATUS',4)
        ){
            $group = 'Normal EC';
        }

        if( $this->checkExistKeyAndValue('SUBSCRIPTION_STATUS',1)
            && !$this->checkExistKeyAndValue('amb_type',1)
            && !$this->checkExistKeyAndValue('PA_CUSTOMER_TYPE',1)
            && !$this->checkExistKeyAndValue('NWC_CAT_STATUS',4)
            && !$this->checkExistKeyAndValue('NWC_CUSTOMER_STATUS',4)
        ){
            $group = 'Subscriber';
        }

        if(
            (
                $this->checkExistKeyAndValue('SUBSCRIPTION_STATUS',1) &&
                (
                    $this->checkExistKeyAndValue('amb_type',1) || $this->checkExistKeyAndValue('PA_CUSTOMER_TYPE',1)
                )
            )
            || $this->checkExistKeyAndValue('NWC_CAT_STATUS',4)
            || $this->checkExistKeyAndValue('NWC_CUSTOMER_STATUS',4)
        ){
            $group = 'Club Member';
        }


        $groupId = $this->findMappingCustomer($group);

        return $groupId;

    }

    /**
     * getCustomerMemberShip
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getCustomerMemberShip(){
        $memberships = array();


        if( $this->checkExistKeyAndValueCustomer('offline_customer',0)){
            $memberships[] = 'On Line Members';
        }

        if( $this->checkExistKeyAndValueCustomer('offline_customer',1)){
            $memberships[] = 'Off Line Members';
        }

        if( $this->checkExistKeyAndValueCustomer('EMP_FLG',1)){
            $memberships[] = 'Employee Members';
        }

        if( $this->checkExistKeyAndValue('BUSINESS_CODE',1,false,true)){
            $memberships[] = 'Invoice Members';
        }

        if( $this->checkExistKeyAndValue('amb_type',1)){
            $memberships[] = 'Ambassador Members';
        }

        if( $this->checkExistKeyAndValue('CNC_Status',1)){
            $memberships[] = 'CNC Members';
        }

        if( $this->checkExistKeyAndValue('CIS_Status',1)){
            $memberships[] = 'CIS Members';
        }

        if($this->checkExistKeyAndValue('MILANO_STATUS',1)){
            $memberships[] = 'Milano Members';
        }

        if($this->checkExistKeyAndValue('ALLEGRIA_STATUS',1)){
            $memberships[] = 'Alegria Members';
        }

        if($this->checkExistKeyAndValue('SATELLITE_FLG',1)){
            $memberships[] = 'Sattelite Members';
        }

        if($this->checkExistKeyAndValue('CHOCOLLATORY_FLG',1)){
            $memberships[] = 'Chocollatory Members';
        }

        if($this->checkExistKeyAndValue('KITKAT_CLUB_FLG',1)){
            $memberships[] = 'Kitkat Members';
        }

        if($this->checkExistKeyAndValue('NWC_CAT_STATUS',4)){
            $memberships[] = 'Wellness club cat Members';
        }

        if($this->checkExistKeyAndValue('NWC_CUSTOMER_STATUS',4)){
            $memberships[] = 'Wellness club Members';
        }

        if($this->checkExistKeyAndValue('WELLNESSCLUB_AMB',1)){
            $memberships[] = 'Wellness Ambassador Members';
        }

        if($this->checkExistKeyAndValue('AMB_FRIENDS',1)){
            $memberships[] = 'Friend Ambassador Members';
        }

        if($this->checkExistKeyAndValue('SATELLITE_AMB',1)){
            $memberships[] = 'Satellite Ambassador Members';
        }

        if ($this->checkExistKeyAndValue('NescafeStandFlg', 1, false, false)) {
            $memberships[] = 'NescafeStand Members';
        }

        $membershipId = array();
        foreach($memberships as $membership){
            $membershipId[] = $this->findMappingCustomer($membership);
        }

        return $membershipId;
    }


    public function checkExistKeyAndValue($key,$value,$checkblank = false,$checkNotEmpty = false){
        if(!isset($this->_aSubProfileData[$key])){

            if($checkblank){
                return true;
            }
            return false;
        }

        if($checkNotEmpty && $this->_aSubProfileData[$key]!= ''){
            return true;
        }

        if($this->_aSubProfileData[$key] == $value){
            return true;
        }

        return false;
    }

    public function getSubProfileValue($key){

        if(isset($this->_aSubProfileData[$key])){
            return $this->_aSubProfileData[$key];
        }

        return false;
    }


    public function checkExistKeyAndValueCustomer($key,$value,$checkblank = false){

        if(!count($this->_aCustomerData)){
            return false;
        }

        if(!isset($this->_aCustomerData[$key])){

            return false;
        }

        if($this->_aCustomerData[$key] == $value){
            return true;
        }

        return false;
    }


    protected function _getSubProfileId($key){
        if(isset($this->_mappingAttributeSubProfile[$key])){
            return $this->_mappingAttributeSubProfile[$key];
        }
        return false;
    }
    /**
     * Get all website Id
     * @return array
     */
    public function getWebsiteCode(){
        $websiteValidate = false;
        try{
            $webList = $this->_websiteRepositoryInterface->getList();
            $websiteId = $this->_storeManager->getStore()->getWebsiteId();
            if(count($webList) > 0){
                foreach ($webList as $web){
                    if($web->getId() == $websiteId){
                        return $web->getCode();
                    }
                }
            }
        }catch (\Magento\Framework\Exception\NoSuchEntityException $e){
            return null;
        }

        return $websiteValidate;
    }

    /**
     * @param array $memberships
     * @param $searchword
     * @return bool
     */
    public function isAmbassadorMembership(array $memberships, $searchword)
    {
        $attribute = $this->_eavConfig->getAttribute('customer', 'membership');
        $options = $attribute->getSource()->getAllOptions();
        $searchOptions= array();
        foreach($options as $key=>$option)
        {
            $memKey = intval($option['value']);
            if(in_array($memKey, $memberships)){
                $searchOptions[] = $option['label']->getText();
            }
        }
        if($this->_matchWord( $searchOptions, $searchword) )
        {
            return true;
        }
        return false;
    }

    /**
     * @param $searchArray
     * @param $searchWord
     * @return bool
     */
    private function _matchWord($searchArray, $searchWord)
    {
        foreach($searchArray as $searchString)
        {
            $arraySearch = explode(' ', strtolower($searchString));
            foreach($arraySearch as $haystack)
            {
                if(strtolower($searchWord) == $haystack){
                    return true;
                }

            }
        }
        return false;
    }
}