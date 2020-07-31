<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */


namespace Riki\SpotOrderApi\Plugin;

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
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $serializer;

    /**
     * Validator constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory $collectionFactory
     * @param \Magento\Catalog\Helper\Data $catalogData
     * @param \Magento\SalesRule\Model\Utility $utility
     * @param \Magento\SalesRule\Model\RulesApplier $rulesApplier
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\SalesRule\Model\Validator\Pool $validators
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param array $data
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
        \Magento\Framework\Serialize\Serializer\Json $serializer = null,
        array $data = []
    )
    {
        $this->_collectionFactory = $collectionFactory;
        $this->_catalogData = $catalogData;
        $this->validatorUtility = $utility;
        $this->rulesApplier = $rulesApplier;
        $this->priceCurrency = $priceCurrency;
        $this->validators = $validators;
        $this->messageManager = $messageManager;
        $this->_request = $request;
        $this->serializer = $serializer ?: \Magento\Framework\App\ObjectManager::getInstance()->get(
            \Magento\Framework\Serialize\Serializer\Json::class
        );
    }

    /**
     * Check data serialized
     *
     * @param $data
     * @return bool
     */
    function checkSerialized($data)
    {
        return (is_string($data) && preg_match("#^((N;)|((a|O|s):[0-9]+:.*[;}])|((b|i|d):[0-9.E-]+;))$#um", $data));
    }


    /**
     * @param \Magento\SalesRule\Model\Validator $subject
     * @param \Closure $proceed
     * @param $item
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function aroundProcess(\Magento\SalesRule\Model\Validator $subject, \Closure $proceed, $item)
    {
        $proceed($item);

        $arrOptions[] = $item->getOptionsByCode()['info_buyRequest'];

        $dataRequest = $this->_request->getRequestData();

        if (isset($dataRequest['cartItem']) && isset($dataRequest['cartItem']['price'])) {
            if (isset($dataRequest['call_spot_order_api']) && $dataRequest['call_spot_order_api'] == "call_spot_order_api") {

                if (strtolower($dataRequest['cartItem']['price']) == 'null' || $dataRequest['cartItem']['price'] == 0) {
                    /**
                     * If price = "null", the price will get latest from Magento DB with applied promotion (if any).
                     */
                    return $item;
                } else {
                    /**
                     * If price <> "null", the price will use from this parameter. Magento need to custom a discount to make price align with Magento logic.
                     */
                    $item = $this->processCartItemWithPriceNotNull($item, $dataRequest, $arrOptions);
                }
            }
        } else {
            if (is_array($arrOptions) && count($arrOptions) > 0) {
                foreach ($arrOptions as $option) {
                    try {
                        $dataOption = $this->serializer->unserialize($option->getValue());
                    } catch (\InvalidArgumentException $e) {
                        continue;
                    }
                    if (isset($dataOption['spot_order_api_discount'])) {
                        $item->setDiscountAmount($dataOption['spot_order_api_discount']);
                        $item->setBaseDiscountAmount($dataOption['spot_order_api_discount']);
                    }
                }
            }
        }

        return $item;
    }

    /**
     * Process data with price not null
     *
     * @param $item
     * @param $dataRequest
     * @param $arrOptions
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function processCartItemWithPriceNotNull($item, $dataRequest, $arrOptions)
    {
        $requestSku = $dataRequest['cartItem']['sku'];
        $expectedPrice = $dataRequest['cartItem']['price'];
        $requestQty = $dataRequest['cartItem']['qty'];
        $productFinalPrice = $item->getBasePriceInclTax();

        $product = $item->getProduct();
        $caseDisplay = $product->getData('case_display');
        $unitQty = ((int)$product->getData('unit_qty') > 0) ? (int)$product->getData('unit_qty') : 1;

        $dataOption['spot_order_api_price'] = $productFinalPrice;
        $dataOption['spot_order_api_discount'] = 0;

        //current item
        if (trim($item->getSku()) == trim($requestSku)) {
            /**
             * only case
             */
            if ($caseDisplay == 2) {
                //$productFinalPrice = $productFinalPrice * $unitQty;
                if ($productFinalPrice > $expectedPrice) {
                    $discount = ($productFinalPrice - $expectedPrice) * $item->getQty();
                } else {
                    $discount = 0;
                }
            } else {
                if ($expectedPrice < $productFinalPrice) {
                    $discount = ($productFinalPrice - $expectedPrice) * $item->getQty();
                } else {
                    $discount = 0;
                }
            }

            if ($expectedPrice == 0) {
                $item->setDiscountAmount($discount);
                $item->setBaseDiscountAmount($discount);
            } else {
                //price >0
                $item->setDiscountAmount($discount);
                $item->setBaseDiscountAmount($discount);
            }

            //set data to option
            if (is_array($arrOptions) && count($arrOptions) > 0) {
                foreach ($arrOptions as $option) {
                    try {
                        $dataOption = $this->serializer->unserialize($option->getValue());
                    } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                        $dataOption = $option->getValue();
                    }

                    if (is_array($dataOption)) {
                        $dataOption['spot_order_api_price'] = $productFinalPrice;
                        $dataOption['spot_order_api_discount'] = $discount;
                        $option->setValue($this->serializer->serialize($dataOption));
                    }
                }
            }
        }
        return $item;
    }

}
