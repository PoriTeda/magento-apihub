<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\MachineApi\Model;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface as Logger;
use \Magento\Quote\Model\QuoteAddressValidator;
use Magento\Framework\Phrase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ShippingInformationManagement extends \Magento\Checkout\Model\ShippingInformationManagement implements \Riki\MachineApi\Api\ShippingInformationManagementInterface
{
    /**
     * @var \Magento\Quote\Api\PaymentMethodManagementInterface
     */
    protected $paymentMethodManagement;

    /**
     * @var PaymentDetailsFactory
     */
    protected $paymentDetailsFactory;

    /**
     * @var \Magento\Quote\Api\CartTotalRepositoryInterface
     */
    protected $cartTotalsRepository;

    /**
     * Quote repository.
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * Logger.
     *
     * @var Logger
     */
    protected $logger;

    /**
     * Validator.
     *
     * @var QuoteAddressValidator
     */
    protected $addressValidator;

    /**
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $addressRepository;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Quote\Model\Quote\TotalsCollector
     */
    protected $totalsCollector;

    /**
     * @var \Magento\Quote\Model\Quote\Item
     */
    protected $_quoteItem ;

    /**
     * @var \Magento\Directory\Model\Region
     */
    protected $_modelRegion;

    /**
     * @var \Magento\Framework\Webapi\Rest\Request
     */
    protected $_request;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @param \Magento\Quote\Api\PaymentMethodManagementInterface $paymentMethodManagement
     * @param \Magento\Checkout\Model\PaymentDetailsFactory $paymentDetailsFactory
     * @param \Magento\Quote\Api\CartTotalRepositoryInterface $cartTotalsRepository
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Quote\Model\QuoteAddressValidator $addressValidator
     * @param Logger $logger
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Quote\Api\PaymentMethodManagementInterface $paymentMethodManagement,
        \Magento\Checkout\Model\PaymentDetailsFactory $paymentDetailsFactory,
        \Magento\Quote\Api\CartTotalRepositoryInterface $cartTotalsRepository,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        QuoteAddressValidator $addressValidator,
        Logger $logger,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\TotalsCollector $totalsCollector,
        \Magento\Quote\Model\Quote\Item $quoteItem,
        \Magento\Directory\Model\Region $modelRegion,
        \Magento\Framework\Webapi\Rest\Request $request,
        \Magento\Framework\Registry $coreRegistry
    ) {
        $this->paymentMethodManagement = $paymentMethodManagement;
        $this->paymentDetailsFactory = $paymentDetailsFactory;
        $this->cartTotalsRepository = $cartTotalsRepository;
        $this->quoteRepository = $quoteRepository;
        $this->addressValidator = $addressValidator;
        $this->logger = $logger;
        $this->addressRepository = $addressRepository;
        $this->scopeConfig = $scopeConfig;
        $this->totalsCollector = $totalsCollector;
        $this->_quoteItem =$quoteItem;
        $this->_modelRegion = $modelRegion;
        $this->_request = $request;
        $this->_coreRegistry = $coreRegistry;
    }

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function processAddressInformation(
        $cartId,
        \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
    ) {
        //set param for machine api
        $this->_request->setParam('call_machine_api','call_machine_api');

        $this->_coreRegistry->register('is_machine_api', true);
        $this->valdateDataInput($addressInformation);
        /** @var \Magento\Checkout\Model\PaymentDetails $paymentDetails */
        $paymentDetails = $this->saveAddressInformation($cartId, $addressInformation);
        $paymentDetails = $this->caculatorDiscountAmount($cartId,$paymentDetails);


        /* process total segment before send response to client */
        $totalSegments = $paymentDetails->getTotals()->getTotalSegments();
        foreach ($totalSegments as $key => $segment){
            if( $segment instanceof \Magento\Quote\Api\Data\TotalSegmentInterface ){
                if($segment->getCode() == 'tax'){
                    unset($totalSegments[$key]);
                }
            }
        }
        $paymentDetails->getTotals()->setTotalSegments($totalSegments);
        return $paymentDetails;
    }

    /**
     * @param $cartId
     * @param $paymentDetails
     * @return mixed
     */
    public function caculatorDiscountAmount($cartId,$paymentDetails){
        $data      = $paymentDetails->getData('totals');
        $totalDiscount = 0;
        $dataitems = $data->getData('items');

        //remove payent free
        $arrPaymentMethod = $paymentDetails->getPaymentMethods();
        foreach ($arrPaymentMethod as $key=> $subMethod){
            if($subMethod->getCode() =='free'){
                unset($arrPaymentMethod[$key]);
            }
        }

        //set grand total
        $totalSegments = $data->getTotalSegments();
        if(isset($totalSegments['grand_total'])){
            $dataBrandTotal = $totalSegments['grand_total'];
            $brandTotalSegments = $dataBrandTotal->getData('value');
            if($brandTotalSegments>=0){
                $data->setGrandTotal($brandTotalSegments);
            }
        }

        $arrPaymentMethod[] =array(
            'code'=>"free",
            'title'=>"Free Of Charge"
        );
        $paymentDetails->setPaymentMethods($arrPaymentMethod);
        return $paymentDetails;
    }

    public function getRegionId($regionCode,$countryId){
        $dataregion = $this->_modelRegion->getCollection()
            ->addFieldToFilter('code',trim($regionCode))
            ->addFieldToFilter('country_id',$countryId)
            ->setCurPage(1)
            ->setPageSize(1);
        if($dataregion && $dataregion->getSize()>0){
            $region = $dataregion->getFirstItem()->getData();
            if(isset($region['region_id'])){
                return $region['region_id'];
            }
        }
        return null;
    }


    public function dataValidate($type,$arrDataValidate){
        $data = $this->_request->getRequestData();
        foreach ($arrDataValidate as $attribute){
            if(isset($data['addressInformation']) && isset($data['addressInformation'][$type]) ) {
                if(isset($data['addressInformation'][$type][$attribute])){
                    if($attribute=='street'){
                        $street = $data['addressInformation'][$type][$attribute];
                        if(empty($street)){
                            throw InputException::requiredField($attribute);
                        }else if(is_array($street)){
                            foreach ($street as $val){
                                if($val==''){
                                    throw InputException::requiredField($attribute);
                                }
                            }
                        }
                    }else{
                        if($data['addressInformation'][$type][$attribute] ==null){
                            throw InputException::requiredField($attribute);
                        }
                    }
                }else{
                    throw InputException::requiredField($attribute);
                }
            }else{
                throw InputException::requiredField($attribute);
            }
        }
    }

    /**
     * @param \Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation
     * @return \Magento\Checkout\Api\Data\ShippingInformationInterface
     */
    public function valdateDataInput(\Magento\Checkout\Api\Data\ShippingInformationInterface $addressInformation){
        $message =array();
        $data = $this->_request->getRequestData();

        $arrDataValidate =array(
            "countryId","regionCode","region","street",
            "telephone","postcode","city","firstname","lastname",
        );


        //validate shiiping address
        $address= $addressInformation->getShippingAddress();

        $this->dataValidate('shippingAddress',$arrDataValidate);

        $addressFisrtNameKana = $address->getCustomAttribute('firstnamekana')->getValue();
        if($addressFisrtNameKana==null){
            throw InputException::requiredField('firstnamekana');
        }

        $addresslastnamekana = $address->getCustomAttribute('lastnamekana')->getValue();
        if($addresslastnamekana==null){
            throw InputException::requiredField('lastnamekana');
        }

        $addressrikiNickname = $address->getCustomAttribute('riki_nickname')->getValue();
        if($addressrikiNickname==null){
            throw InputException::requiredField('riki_nickname');
        }

        //billingAddress
        $billingAddress= $addressInformation->getBillingAddress();
        //$arrDataValidate[]='saveInAddressBook';
        $this->dataValidate('billingAddress',$arrDataValidate);


        $billingAddressFisrtNameKana = $billingAddress->getCustomAttribute('firstnamekana')->getValue();
        if($billingAddressFisrtNameKana==null){
            throw InputException::requiredField('firstnamekana');
        }

        $billingAddresslastnamekana = $billingAddress->getCustomAttribute('lastnamekana')->getValue();
        if($billingAddresslastnamekana==null){
            throw InputException::requiredField('lastnamekana');
        }

        $billingAddressrikiNickname = $billingAddress->getCustomAttribute('riki_nickname')->getValue();
        if($billingAddressrikiNickname==null){
            throw InputException::requiredField('riki_nickname');
        }

        if($billingAddress->getSaveInAddressBook() !=0){
            throw new NoSuchEntityException(__('Sory! This value always false.',array(array("fieldName"=>'saveInAddressBook'))));
        }

        //shpping address
        $regionCodeShipping   ='';
        $regionCodeShippingId = '';
        if(isset($data['addressInformation']) && isset($data['addressInformation']['shippingAddress'])){
            $shippingAddress = $data['addressInformation']['shippingAddress'];
            if(isset($shippingAddress['regionCode']) && $shippingAddress['regionCode'] !='' ){
                $regionId = $this->getRegionId($shippingAddress['regionCode'],$address->getCountryId());
                if($regionId){
                    $regionCodeShipping   = $shippingAddress['regionCode'];
                    $regionCodeShippingId = $regionId;
                    $address->setRegionId($regionId);
                    $addressInformation->setShippingAddress($address);
                }else{
                    throw new NoSuchEntityException(__('Sorry! The region code of shipping address has not been found.',array(array("fieldName"=>"regionCode"))));
                }
            }else{
                throw InputException::requiredField('regionCode');
            }
        }

        //billingAddress
        if(isset($data['addressInformation']) && isset($data['addressInformation']['billingAddress'])){
            $billing = $data['addressInformation']['billingAddress'];
            if(isset($billing['regionCode']) && $billing['regionCode'] !='' ){
                if($regionCodeShipping ==$billing['regionCode'] ){
                    $billingAddress->setRegionId($regionCodeShippingId);
                    $addressInformation->setBillingAddress($billingAddress);
                }else{
                    $regionIBillingId = $this->getRegionId($billing['regionCode'],$billing['countryId']);
                    if($regionIBillingId){
                        $billingAddress->setRegionId($regionIBillingId);
                        $addressInformation->setBillingAddress($billingAddress);
                    }else{
                        throw new NoSuchEntityException(__('Sorry! The region code of billing address has not been found.',array(array("fieldName"=>"regionCode"))));
                    }
                }
            }else{
                throw InputException::requiredField('regionCode');
            }
        }

        return $addressInformation;
    }


}
