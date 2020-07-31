<?php
namespace Riki\PurchaseRestriction\Helper;

use \Riki\PurchaseRestriction\Model\Config\Source\Product\DurationUnit;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const ERROR_PURCHASE_RESTRICTION = 'error_purchase_restriction';

    /** @var \Riki\PurchaseRestriction\Model\ResourceModel\PurchaseHistory  */
    protected $_purchasedHistoryResource;

    /** @var \Magento\Framework\Stdlib\DateTime\DateTime  */
    protected $_date;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface  */
    protected $_scopeConfig;

    /** @var \Magento\Config\Model\Config\Source\Locale\Weekdays  */
    protected $_weekdaysConfig;

    /** @var array  */
    protected $_quoteItemToValidationData = [];

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Riki\PurchaseRestriction\Model\ResourceModel\PurchaseHistory $purchaseHistoryResource
     * @param \Magento\Config\Model\Config\Source\Locale\Weekdays $weekdays
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Riki\PurchaseRestriction\Model\ResourceModel\PurchaseHistory $purchaseHistoryResource,
        \Magento\Config\Model\Config\Source\Locale\Weekdays $weekdays
    ){
        $this->_purchasedHistoryResource = $purchaseHistoryResource;
        $this->_date = $date;
        $this->_scopeConfig = $context->getScopeConfig();
        $this->_weekdaysConfig = $weekdays;

        parent::__construct($context);
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @return bool
     */
    public function validatePurchaseRestrictionQuoteItem(\Magento\Quote\Model\Quote\Item $quoteItem){
        return $this->validatePurchaseRestrictionQuoteItemWithQty($quoteItem, $quoteItem->getQty());
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @param $qty
     * @return bool
     */
    public function validatePurchaseRestrictionQuoteItemWithQty(\Magento\Quote\Model\Quote\Item $quoteItem, $qty){
        $validationData = $this->getValidationDataByQuoteItem($quoteItem);

        $totalQty = $this->getProductBuyRequestQtyByQuoteItem($quoteItem);

        $totalQty += $qty - $quoteItem->getQty();

        if($validationData->hasData('duration_unit')){
            return $validationData->getRestrictionQty() >= ($totalQty + $validationData->getPurchasedQty());
        }

        return true;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @return bool|\Magento\Framework\DataObject
     */
    public function getValidationDataByQuoteItem(\Magento\Quote\Model\Quote\Item $quoteItem){

        $itemId = $quoteItem->getId();

        if(
            !$itemId ||
            !isset($this->_quoteItemToValidationData[$itemId])
        ){
            $result = new \Magento\Framework\DataObject();

            $product = $quoteItem->getProduct();

            $durationUnit = $product->getLimitUserUnit();
            $durationNumber = $product->getLimitUserDuration();
            $restrictionQty = $product->getLimitUserQty();

            if($durationUnit && $restrictionQty){

                $data = [];

                $unitQty = 1;

                if($quoteItem->getUnitCase() == \Riki\CreateProductAttributes\Model\Product\UnitEc::UNIT_CASE){

                    if($quoteItem->getUnitQty()){
                        $unitQty = $quoteItem->getUnitQty();
                    }else{
                        $unitQtyProduct = $quoteItem->getProduct()->getUnitQty();

                        if($unitQtyProduct)
                            $unitQty = $unitQtyProduct;
                    }
                }


                $data['unit_qty'] = $unitQty;

                $data['duration_unit'] = $durationUnit;
                $data['duration_number'] = $durationNumber;
                $data['restriction_qty'] = $restrictionQty;

                $fromDate = false;

                if($durationNumber){
                    switch($durationUnit){
                        case DurationUnit::UNIT_DAY:
                            $currentTimestamp = $this->_date->timestamp();
                            $fromDateTimeStamp = $currentTimestamp - $durationNumber * 24 * 60 * 60;
                            $fromDate = $this->_date->date('Y-m-d', $fromDateTimeStamp);
                            break;
                        case DurationUnit::UNIT_WEEK:
                            $fromDate = $this->getFirstDayOfWeek($durationNumber);
                            break;
                        case DurationUnit::UNIT_MONTH:
                            $fromDate = $this->getFirstDayOfMonth($durationNumber);
                            break;
                        default:
                            return true;
                    }
                }

                $purchasedQty = $this->_purchasedHistoryResource->sumPurchasedQtyByQuoteItem($quoteItem, $fromDate);
                $data['purchased_qty'] = $purchasedQty;

                $result->addData($data);
            }

            if (!$itemId) {
                return $result;
            }

            $this->_quoteItemToValidationData[$itemId] = $result;
        }

        return $this->_quoteItemToValidationData[$itemId];
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @return float|int|mixed
     */
    public function getProductBuyRequestQtyByQuoteItem(\Magento\Quote\Model\Quote\Item $quoteItem){
        $qty = 0;

        $quote = $quoteItem->getQuote();

        $productId = $quoteItem->getProductId();

        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach($quote->getAllVisibleItems() as $item){
            if($item->getProductId() == $productId)
                $qty += $item->getQty();
        }

        return $qty;
    }

    /**
     * @param $durationNumber
     * @return string
     */
    protected function getFirstDayOfWeek($durationNumber){
        $currentTimestamp = $this->_date->timestamp();

        $fromDateTimeStamp = $currentTimestamp - ($durationNumber - 1) * 7 * 24 * 60 * 60;

        return $this->_date->date('Y-m-d', $this->_date->timestamp(strtotime($this->getFirstDayOfWeekLabelConfig(), $fromDateTimeStamp)));
    }

    /**
     * @param $durationNumber
     * @return string
     */
    protected function getFirstDayOfMonth($durationNumber){
        $currentTimestamp = $this->_date->timestamp();

        return $this->_date->date('Y-m-d', $this->_date->timestamp(strtotime('first day of -' . ($durationNumber - 1) . ' month', $currentTimestamp)));
    }

    /**
     * @return string
     */
    public function getFirstDayOfWeekLabelConfig(){
        $configValue = $this->_scopeConfig->getValue(
            'general/locale/firstday',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $options = $this->_weekdaysConfig->toOptionArray();

        foreach($options as $option){
            if($option['value'] == $configValue)
                return $option['label'];
        }

        return 'Monday';
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @return \Magento\Framework\Phrase|string
     */
    public function generateErrorMessage(\Magento\Quote\Model\Quote\Item $quoteItem){
        $validationData = $this->getValidationDataByQuoteItem($quoteItem);

        if($validationData->hasData('duration_unit')){

            $productName = $quoteItem->getName();

            $durationUnit = $this->getLabelOfDurationUnit($validationData->getDurationUnit());

            $durationNumber = $validationData->getDurationNumber();

            $restrictionQty = $validationData->getRestrictionQty();

            $purchasedQty = $validationData->getPurchasedQty();

            $unitQty = $validationData->getUnitQty();

            $availableQty = floor(($restrictionQty - $purchasedQty) / $unitQty);

            $restrictionQtyMsg = floor($restrictionQty / $unitQty);

            if($unitQty > 1){ // cases product
                if($availableQty > 0){
                    if($validationData->getDurationNumber()){
                        return __('Please change the qty up to %1. This case product is limited to purchase %2 qty during %3%4 period', $availableQty, $restrictionQtyMsg, $durationNumber, $durationUnit, $productName);
                    }else{
                        return __('Please change the qty up to %1. This case product is limited to purchase %2 qty per customer', $availableQty, $restrictionQtyMsg, $productName);
                    }
                }else{
                    if($validationData->getDurationNumber()){
                        return __('You can\'t purchase %1 anymore. This case product is limited to purchase %2 qty during %3%4 period', $productName, $restrictionQtyMsg, $durationNumber, $durationUnit);
                    }else{
                        return __('You can\'t purchase %1 anymore. This case product is limited to purchase %2 qty per customer', $productName, $restrictionQtyMsg);
                    }
                }
            }else{
                if($availableQty > 0){
                    if($validationData->getDurationNumber()){
                        return __('Please change the qty up to %1. This product is limited to purchase %2 qty during %3%4 period', $availableQty, $restrictionQtyMsg, $durationNumber, $durationUnit, $productName);
                    }else{
                        return __('Please change the qty up to %1. This product is limited to purchase %2 qty per customer', $availableQty, $restrictionQtyMsg, $productName);
                    }
                }else{
                    if($validationData->getDurationNumber()){
                        return __('You can\'t purchase %1 anymore. This product is limited to purchase %2 qty during %3%4 period', $productName, $restrictionQtyMsg, $durationNumber, $durationUnit);
                    }else{
                        return __('You can\'t purchase %1 anymore. This product is limited to purchase %2 qty per customer', $productName, $restrictionQtyMsg);
                    }
                }
            }
        }

        return '';
    }

    /**
     * @param $value
     * @return \Magento\Framework\Phrase|string
     */
    public function getLabelOfDurationUnit($value){
        switch($value){
            case DurationUnit::UNIT_DAY:
                $result = __('day');
                break;
            case DurationUnit::UNIT_WEEK:
                $result = __('week');
                break;
            case DurationUnit::UNIT_MONTH:
                $result = __('month');
                break;
            default:
                $result = '';
                break;
        }

        return $result;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $quoteItem
     * @return bool
     */
    public function needToValidate(\Magento\Quote\Model\Quote\Item $quoteItem){
        $quote = $quoteItem->getQuote();
        if(
            $quoteItem->getParentItemId() ||
            is_null($quote) ||
            $quote->getCustomerIsGuest() ||
            $quote->getData('is_generate') // skip generate next order, simulated order
        )
            return false;

        return true;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return $this
     */
    public function removeError(\Magento\Quote\Model\Quote\Item $item){

        $code = \Riki\PurchaseRestriction\Helper\Data::ERROR_PURCHASE_RESTRICTION;

        if ($item->getHasError()) {
            $params = ['origin' => 'riki_purchase_restriction', 'code' => $code];
            $item->removeErrorInfosByParams($params);
        }

        $quote = $item->getQuote();

        if(is_null($quote))
            return $this;

        $quoteItems = $quote->getItemsCollection();
        $canRemoveErrorFromQuote = true;

        foreach ($quoteItems as $quoteItem) {
            if ($quoteItem->getItemId() == $item->getItemId()) {
                continue;
            }

            $errorInfos = $quoteItem->getErrorInfos();
            foreach ($errorInfos as $errorInfo) {
                if ($errorInfo['code'] == $code) {
                    $canRemoveErrorFromQuote = false;
                    break;
                }
            }

            if (!$canRemoveErrorFromQuote) {
                break;
            }
        }

        if ($quote->getHasError() && $canRemoveErrorFromQuote) {
            $params = ['origin' => 'cataloginventory', 'code' => $code];
            $quote->removeErrorInfosByParams(null, $params);
        }

        return $this;
    }
}
