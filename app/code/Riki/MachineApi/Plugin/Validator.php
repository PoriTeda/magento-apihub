<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */



namespace Riki\MachineApi\Plugin;

use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Item\AbstractItem;

/**
 * SalesRule Validator Model
 *
 * Allows dispatching before and after events for each controller action
 *
 * @method mixed getCouponCode()
 * @method Validator setCouponCode($code)
 * @method mixed getWebsiteId()
 * @method Validator setWebsiteId($id)
 * @method mixed getCustomerGroupId()
 * @method Validator setCustomerGroupId($id)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Validator extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Rule source collection
     *
     * @var \Magento\SalesRule\Model\ResourceModel\Rule\Collection
     */
    protected $_rules;

    /**
     * Defines if method \Magento\SalesRule\Model\Validator::reset() wasn't called
     * Used for clearing applied rule ids in Quote and in Address
     *
     * @var bool
     */
    protected $_isFirstTimeResetRun = true;

    /**
     * Information about item totals for rules
     *
     * @var array
     */
    protected $_rulesItemTotals = [];

    /**
     * Skip action rules validation flag
     *
     * @var bool
     */
    protected $_skipActionsValidation = false;

    /**
     * Catalog data
     *
     * @var \Magento\Catalog\Helper\Data|null
     */
    protected $_catalogData = null;

    /**
     * @var \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var \Magento\SalesRule\Model\Utility
     */
    protected $validatorUtility;

    /**
     * @var \Magento\SalesRule\Model\RulesApplier
     */
    protected $rulesApplier;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var \Magento\SalesRule\Model\Validator\Pool
     */
    protected $validators;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\Webapi\Rest\Request
     */
    protected $_request;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json|null
     */
    private $serializer;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory $collectionFactory
     * @param \Magento\Catalog\Helper\Data $catalogData
     * @param \Magento\SalesRule\Model\Utility $utility
     * @param \Magento\SalesRule\Model\RulesApplier $rulesApplier
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\SalesRule\Model\Validator\Pool $validators
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\Webapi\Rest\Request $request
     * @param array $data
     * @param \Magento\Framework\Serialize\Serializer\Json|null $serializer
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory $collectionFactory,
        \Magento\Catalog\Helper\Data $catalogData,
        \Magento\SalesRule\Model\Utility $utility,
        \Magento\SalesRule\Model\RulesApplier $rulesApplier,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\SalesRule\Model\Validator\Pool $validators,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Webapi\Rest\Request $request,
        array $data = [],
        \Magento\Framework\Serialize\Serializer\Json $serializer = null
    ) {
        $this->_collectionFactory = $collectionFactory;
        $this->_catalogData = $catalogData;
        $this->validatorUtility = $utility;
        $this->rulesApplier = $rulesApplier;
        $this->priceCurrency = $priceCurrency;
        $this->validators = $validators;
        $this->messageManager = $messageManager;
        $this->_request = $request;
        //parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $this->serializer = $serializer ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Serialize\Serializer\Json::class);
    }



    /**
     * check request from web api
     *
     * @return bool
     */
    public function checkRequestWebApi(){
        $pathInfo =  $this->_request->getPathInfo();
        $patternStep5 ='#V1/mm/carts/order/payment-information#';
        if(preg_match($patternStep5,$pathInfo,$match)){
            return true;
        }

        $pattern ='#/V1/mm/carts/#';
        if(preg_match($pattern,$pathInfo,$match)){
            return true;
        }
        return false;

    }

    /**
     * Check data serialized
     *
     * @param $data
     * @return bool
     */
    function checkSerialized($data){
        return (is_string($data) && preg_match("#^((N;)|((a|O|s):[0-9]+:.*[;}])|((b|i|d):[0-9.E-]+;))$#um", $data));
    }


    /**
     * Quote item discount calculation process
     *
     * @param AbstractItem $item
     * @return $this
     */
    public function aroundProcess(\Magento\SalesRule\Model\Validator $subject, \Closure $proceed, $item)
    {
        $proceed($item);

        /** Set data machinery for buy request */
        $arrOptions =$item->getOptions();

        $dataRequest = $this->_request->getRequestData();
        if(isset($dataRequest['cartItem']) && isset($dataRequest['cartItem']['price'])){
            if(isset($dataRequest['call_machine_api']) && $dataRequest['call_machine_api']=="call_machine_api"){
                $requestSku        = $dataRequest['cartItem']['sku'];
                $expectedPrice     = $dataRequest['cartItem']['price'];
                $requestQty        = $dataRequest['cartItem']['qty'];
                $productFinalPrice = $item->getBasePriceInclTax();

                $dataOption['machine_price']       = $productFinalPrice;
                $dataOption['machine_discount']    = 0 ;

                //current item
                if(trim($item->getSku()) == trim($requestSku)){
                    $item->setQty($requestQty);

                    if($expectedPrice < $productFinalPrice){
                        $discount = ($productFinalPrice - $expectedPrice)*$item->getQty();
                    }else{
                        $discount = 0;
                    }

                    if($expectedPrice==0){
                        $item->setDiscountAmount($discount);
                        $item->setBaseDiscountAmount($discount);
                    }else{
                        //price >0
                        $item->setDiscountAmount($discount);
                        $item->setBaseDiscountAmount($discount);
                    }

                    if (is_array($arrOptions) && count($arrOptions) > 0) {
                        foreach ($arrOptions as $option) {
                            try {
                                $dataOption = $this->serializer->unserialize($option->getValue());
                            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                                $dataOption = $option->getValue();
                            }

                            if (is_array($dataOption)) {
                                $dataOption['machine_price'] = $productFinalPrice;
                                $dataOption['machine_discount'] = $discount;
                                $option->setValue($this->serializer->serialize($dataOption));
                            }
                        }
                    }
                }
            }

        }else{
            if (is_array($arrOptions) && count($arrOptions) > 0) {
                foreach ($arrOptions as $option) {

                    try {
                        $dataOption = $this->serializer->unserialize($option->getValue());
                    } catch (\InvalidArgumentException $e) {
                        continue;
                    }

                    if(isset($dataOption['machine_discount'])){
                        $item->setDiscountAmount($dataOption['machine_discount']);
                        $item->setBaseDiscountAmount($dataOption['machine_discount']);
                    }
                }
            }
        }
        return $item;
    }
}
