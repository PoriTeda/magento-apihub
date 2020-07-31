<?php
namespace Riki\Sales\Plugin\Block\Adminhtml\Order\View\Items\Renderer;

class DefaultRenderer
{
    /**
     * generate custom content for gift wrapping and discount amount column
     *
     * @param \Magento\Sales\Block\Adminhtml\Order\View\Items\Renderer\DefaultRenderer $subject
     * @param \Closure $proceed
     * @param $code
     * @param bool|false $strong
     * @param string $separator
     * @return string
     */
    public function aroundDisplayPriceAttribute(
        \Magento\Sales\Block\Adminhtml\Order\View\Items\Renderer\DefaultRenderer $subject,
        \Closure $proceed,
        $code, $strong = false, $separator = '<br />'
    ) {

        if($code === 'gw_price'){

            $priceDataObject = $subject->getPriceDataObject();

            $unitQty = $subject->getItem()->getUnitQty()? $subject->getItem()->getUnitQty() : 1;

            $qty = $subject->getItem()->getQtyOrdered() / $unitQty;

            return $subject->displayPrices(
                ($priceDataObject->getData('gw_base_price') +  $priceDataObject->getData('gw_base_tax_amount')) * $qty,
                ($priceDataObject->getData($code) + $priceDataObject->getData('gw_tax_amount')) * $qty,
                $strong,
                $separator
            );
        }elseif($code == 'discount_amount'){
            $result = $subject->displayPrices(
                $subject->getPriceDataObject()->getData('base_' . $code),
                $subject->getPriceDataObject()->getData($code),
                $strong,
                $separator
            );

            //do not show promotion with machine maintenance
            if ($subject->getOrder()->getOrderChannel() !='machine_maintenance') {
                $result .= $this->generateDiscountInfoByOrderItem($subject->getItem());
            }

            return $result;
        }

        return $proceed($code, $strong, $separator);
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $item
     * @return string
     */
    protected function generateDiscountInfoByOrderItem(\Magento\Sales\Model\Order\Item $item){
        $discountInfo = $item->getAppliedRulesBreakdown();

        $result = '';

        if(!empty($discountInfo)){

            try{
                $discountIdToAmount = \Zend_Json::decode($discountInfo);

                foreach($discountIdToAmount as $ruleId  =>  $amount){

                    if((float)$amount > 0){
                        $result .= '</br>';

                        $result .= __('Promotion ID %1: %2', $ruleId, $item->getOrder()->formatPricePrecision($amount, 2));
                    }
                }

            }catch (\Exception $e){
                return $result;
            }
        }

        return $result;
    }
}
