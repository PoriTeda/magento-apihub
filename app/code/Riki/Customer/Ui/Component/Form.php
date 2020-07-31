<?php
namespace Riki\Customer\Ui\Component;

/**
 * Class Form
 * @package Riki\Customer\Ui\Component
 */
class Form
{
    /**
     * @var \Magento\Framework\Registry $_coreRegistry
     */
    private $_coreRegistry;

    /**
     * Form constructor.
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Framework\Registry $registry
    )
    {
        $this->_coreRegistry = $registry;
    }

    /**
     * Rewrite UI form for copy customer function 
     * @param $subject
     * @param $dataSource
     * @return array
     */
    public function afterGetDataSourceData($subject,$dataSource){
     
     $cloneDataSource = $this->_coreRegistry->registry('clone_customer_data');
     $isClone = !empty($cloneDataSource);
     if($isClone){
         $dataSource['customer'] = $cloneDataSource['account'];
         $dataAccount = array();
         if(isset($cloneDataSource['account'])){
             foreach ($cloneDataSource['account'] as $customerKey => $customerValue){
                 if($customerValue == false || $customerValue == ''){
                     continue;
                 }
                 $dataAccount[$customerKey] = $customerValue;
             }
         }

         $dataAddress = array();
         if(isset($cloneDataSource['address'])){
             $inc = '0';
             foreach ($cloneDataSource['address'] as $key => $address){

                 foreach ($address as $k => $v){
                     if($v == false || $v == 'false' ||$k == 'customer_id' || $k == 'id'){
                         continue;
                     }
                     $addressItem[$k] = $v  ;
                 }
                 $dataAddress['new_'.$inc] = $addressItem;
                 $inc+=1;
             }
         }
         $dataSource['customer'] = $dataAccount;
         $dataSource['address'] = $dataAddress;

         unset($dataSource['address']['id']);
         unset($dataSource['account']);
         unset($dataSource['customer']['email']);
         unset($dataSource['customer']['email_2']);
     }
     if(!$isClone){
         return $dataSource;
     }else{
         $this->_coreRegistry->unregister('clone_customer_data');
         return ['data'=>$dataSource];
     }
  }
}