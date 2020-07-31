<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Customer\Model\Customer;

use Magento\Customer\Model\Address;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\FileProcessorFactory;
use Magento\Customer\Model\ResourceModel\Customer\Collection;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Eav\Model\Config;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\FilterPool;
use Magento\Ui\DataProvider\EavValidationRules;

/**
 * Class DataProvider
 */
class DataProvider extends \Magento\Customer\Model\Customer\DataProvider
{
    /**
     * @var \Magento\Framework\Registry $_coreRegistry
     */
    protected $_coreRegistry;

    /**
     * @var \Riki\Customer\Helper\Region
     */
    protected $_regionHelper;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        EavValidationRules $eavValidationRules,
        CustomerCollectionFactory $customerCollectionFactory,
        Config $eavConfig,
        FilterPool $filterPool,
        \Riki\Customer\Helper\Region $regionHelper,
        \Magento\Framework\Registry $coreRegistry,
        FileProcessorFactory $fileProcessorFactory = null,
        array $meta = [],
        array $data = [],
        ContextInterface $context = null,
        $allowToShowHiddenAttributes = true
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_regionHelper = $regionHelper;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $eavValidationRules,
            $customerCollectionFactory, $eavConfig, $filterPool, $fileProcessorFactory, $meta, $data, $context,
            $allowToShowHiddenAttributes);
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        $registryData = $this->_coreRegistry->registry('consumer_customer_response');

        if (!empty($registryData)) {
            $consumerData = $registryData;
        }

        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $items = $this->collection->getItems();
        /** @var Customer $customer */
        foreach ($items as $customer) {
            if(isset($consumerData)){
                foreach ($consumerData as $key =>$value){
                    if(count($value) >0){
                        foreach ($value as $k=>$v){
                            $customer->setData($k,$v);
                        }
                    }
                }
            }
            $result['customer'] = $customer->getData();
            unset($result['address']);

            /** @var Address $address */
            foreach ($customer->getAddresses() as $address) {
                $addressId = $address->getId();
                $address->load($addressId);
                $result['address'][$addressId] = $address->getData();
                $this->prepareAddressData($addressId, $result['address'], $result['customer']);
            }
            $this->loadedData[$customer->getId()] = $result;
        }
        $this->_coreRegistry->unregister('consumer_customer_response');
        return $this->loadedData;
    }

    /**
     * Prepare address data
     *
     * @param int $addressId
     * @param array $addresses
     * @param array $customer
     * @return void
     */
    protected function prepareAddressData($addressId, array &$addresses, array $customer)
    {
        $consumerData = $this->_coreRegistry->registry('consumer_customer_response');
        if(isset($addresses[$addressId]['riki_type_address']) && $addresses[$addressId]['riki_type_address'] == "home"){
            $street = "";
            $street .= isset($customer['KEY_ADDRESS2']) ? $customer['KEY_ADDRESS2'] :"" ." ";
            $street .= isset($customer['KEY_ADDRESS3']) ? $customer['KEY_ADDRESS3'] :"" ." ";
            $street .= isset($customer['KEY_ADDRESS4']) ? $customer['KEY_ADDRESS4'] :"";
            $addresses[$addressId]['KEY_ADDRESS2'] =  isset($customer['KEY_ADDRESS2']) ? $customer['KEY_ADDRESS2'] :"" ;
            $addresses[$addressId]['KEY_ADDRESS3'] =  isset($customer['KEY_ADDRESS3']) ? $customer['KEY_ADDRESS3'] :"" ;
            $addresses[$addressId]['KEY_ADDRESS4'] =  isset($customer['KEY_ADDRESS4']) ? $customer['KEY_ADDRESS4'] :"" ;
            $addresses[$addressId]['street'] = $street;
            $normalAddress = '';
            if(isset($consumerData['customer_api']['KEY_ADDRESS2']) && '' != $consumerData['customer_api']['KEY_ADDRESS2']){
                $normalAddress .= $consumerData['customer_api']['KEY_ADDRESS2'];
            }

            if(isset($consumerData['customer_api']['KEY_ADDRESS3']) && '' != $consumerData['customer_api']['KEY_ADDRESS3']){
                $normalAddress .= ' '.$consumerData['customer_api']['KEY_ADDRESS3'];
            }

            if(isset($consumerData['customer_api']['KEY_ADDRESS4']) && '' != $consumerData['customer_api']['KEY_ADDRESS4']){
                $normalAddress .= ' '.$consumerData['customer_api']['KEY_ADDRESS4'];
            }

            if('' != $normalAddress){
                $normalAddress = trim($normalAddress);
                $addresses[$addressId]['street'] = $normalAddress;
            }
            //normal
            if(isset($consumerData['customer_api']['KEY_ADDRESS1'])){
                //set state province
                $regionId = $this->_regionHelper->getRegionIdByName($consumerData['customer_api']['KEY_ADDRESS1']);
                if($regionId){
                    $addresses[$addressId]['region_id'] = $regionId;
                }
            }
        }else if(isset($addresses[$addressId]['riki_type_address']) && $addresses[$addressId]['riki_type_address'] == "company"){
            //ambassador
            $addressAmbName = '';
            if(isset($consumerData['amb_api']['COM_ADDRESS2']) && '' != $consumerData['amb_api']['COM_ADDRESS2']){
                $addressAmbName .= ' '.$consumerData['amb_api']['COM_ADDRESS2'];
            }

            if(isset($consumerData['amb_api']['COM_ADDRESS3']) && '' != $consumerData['amb_api']['COM_ADDRESS3']){
                $addressAmbName .= ' '.$consumerData['amb_api']['COM_ADDRESS3'];
            }

            if(isset($consumerData['amb_api']['COM_ADDRESS4']) && '' != $consumerData['amb_api']['COM_ADDRESS4']){
                $addressAmbName .= ' '.$consumerData['amb_api']['COM_ADDRESS4'];
            }

            if('' != $addressAmbName){
                $addressAmbName = trim($addressAmbName);
                $addresses[$addressId]['street'] = $addressAmbName;
            }
            //ambassador
            if(isset($consumerData['amb_api']['COM_ADDRESS1'])){
                //set state province
                $regionId = $consumerData['amb_api']['COM_ADDRESS1'];
                if($regionId){
                    $addresses[$addressId]['region_id'] = $regionId;
                }
            }

        }
        if (isset($customer['default_billing'])
            && $addressId == $customer['default_billing']
        ) {
            $addresses[$addressId]['default_billing'] = $customer['default_billing'];

            // fill info for ambassador customer & apply for all address
            if(!empty($consumerData)){
                $addresses[$addressId] = $this->updateCustomerAddressName($addressId,$addresses,$customer,$consumerData);
            }


        }
        if (isset($customer['default_shipping'])
            && $addressId == $customer['default_shipping']
        ) {
            $addresses[$addressId]['default_shipping'] = $customer['default_shipping'];
        }


        if (isset($addresses[$addressId]['street'])) {
            $addresses[$addressId]['street'] = explode("\n", $addresses[$addressId]['street']);
        }

    }

    /**
     * UpdateCustomerAddressName
     *
     * @param $addressId
     * @param array $addresses
     * @param array $customer
     * @param $consumerData
     * @return mixed
     */
    public function updateCustomerAddressName($addressId, array $addresses, array $customer, $consumerData){

        //normal
        if(isset($consumerData['customer_api']['KEY_ADDRESS_LAST_NAME']) && '' != $consumerData['customer_api']['KEY_ADDRESS_LAST_NAME']){
            $addresses[$addressId]['lastname'] = $consumerData['customer_api']['KEY_ADDRESS_LAST_NAME'];
        }

        if(isset($consumerData['customer_api']['KEY_ADDRESS_FIRST_NAME']) && '' != $consumerData['customer_api']['KEY_ADDRESS_FIRST_NAME']){
            $addresses[$addressId]['firstname'] = $consumerData['customer_api']['KEY_ADDRESS_FIRST_NAME'];
        }

        if(isset($consumerData['customer_api']['KEY_ADDRESS_LAST_NAME_KANA']) && '' != $consumerData['customer_api']['KEY_ADDRESS_LAST_NAME_KANA']){
            $addresses[$addressId]['lastnamekana'] = $consumerData['customer_api']['KEY_ADDRESS_LAST_NAME_KANA'];
        }

        if(isset($consumerData['customer_api']['KEY_ADDRESS_FIRST_NAME_KANA']) && '' != $consumerData['customer_api']['KEY_ADDRESS_FIRST_NAME_KANA']){
            $addresses[$addressId]['firstnamekana'] = $consumerData['customer_api']['KEY_ADDRESS_FIRST_NAME_KANA'];
        }


        if(isset($consumerData['customer_api']['KEY_COMPANY_NAME']) && '' != $consumerData['customer_api']['KEY_COMPANY_NAME']){
            $addresses[$addressId]['company'] = $consumerData['customer_api']['KEY_COMPANY_NAME'];
        }

        if(isset($consumerData['customer_api']['KEY_PHONE_NUMBER']) && '' != $consumerData['customer_api']['KEY_PHONE_NUMBER']){
            $addresses[$addressId]['telephone'] = $consumerData['customer_api']['KEY_PHONE_NUMBER'];
        }

        return $addresses[$addressId];
    }
}
