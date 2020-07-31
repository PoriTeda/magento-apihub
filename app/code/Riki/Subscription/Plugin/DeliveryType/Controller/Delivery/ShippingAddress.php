<?php
namespace Riki\Subscription\Plugin\DeliveryType\Controller\Delivery;
class ShippingAddress
{
    /**
     * check seasonal skip date
     *
     * @param \Riki\DeliveryType\Controller\Delivery\ShippingAddress $subject
     * @param $data
     * @return mixed
     */
    public function afterPreparedResultData(
        \Riki\DeliveryType\Controller\Delivery\ShippingAddress $subject,
        $data
    ) {
        if(is_array($data)){
            $quote = $subject->getQuote();
            if($quote->getRikiCourseId()){
                foreach($data as $index =>  $deliveryGroup){
                    if(isset($deliveryGroup['cartItems'])){
                        $allowSkipDate = [];
                        foreach($deliveryGroup['cartItems'] as $cartItemId){
                            $quoteItem = $quote->getItemById($cartItemId);
                            if($quoteItem){
                                $product = $quoteItem->getProduct();
                                if(
                                    $product->getAllowSeasonalSkip() &&
                                    !$product->getSeasonalSkipOptional() &&
                                    $product->getAllowSkipFrom() &&
                                    $product->getAllowSkipTo()
                                ){
                                    $allowSkipDate[] = [
                                        'allow_skip_from'   =>  $product->getAllowSkipFrom(),
                                        'allow_skip_to'   =>  $product->getAllowSkipTo()
                                    ];
                                }
                            }
                        }
                        $data[$index]['allow_skip_date'] = $allowSkipDate;
                    }
                }
            }
        }
        return $data;
    }
}