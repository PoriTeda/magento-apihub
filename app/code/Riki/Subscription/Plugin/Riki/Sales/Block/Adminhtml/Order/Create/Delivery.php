<?php

namespace Riki\Subscription\Plugin\Riki\Sales\Block\Adminhtml\Order\Create;

class Delivery
{

    /**
     * add back order data
     *
     * @param \Riki\Sales\Block\Adminhtml\Order\Create\Delivery $subject
     * @param array $result
     * @return array
     */
    public function afterPrepareGroupDeliveryAddressData(
        \Riki\Sales\Block\Adminhtml\Order\Create\Delivery $subject,
        array $result
    ){

        $quote = $subject->getQuote();

        if($quote->getRikiCourseId()){

            foreach($result as $index   =>  $resultData){
                if(isset($resultData['ddate_info'])){
                    foreach($resultData['ddate_info'] as $deliveryType =>  $deliveryGroup){
                        if(isset($deliveryGroup['cartItems'])){

                            $allowSkipDate = [];

                            foreach($deliveryGroup['cartItems'] as $cartItem){
                                $quoteItem = $quote->getItemById($cartItem['quote_item_id']);

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

                                        if(
                                            !isset($result[$index]['ddate_info'][$deliveryType]['allow_skip_from']) ||
                                            $product->getAllowSkipFrom() > $result[$index]['ddate_info'][$deliveryType]['allow_skip_from']
                                        ){
                                            $result[$index]['ddate_info'][$deliveryType]['allow_skip_from'] = $product->getAllowSkipFrom();
                                        }
                                        if(
                                            !isset($result['ddate_info'][$deliveryType]['allow_skip_to']) ||
                                            $product->getAllowSkipTo() < $result[$index]['ddate_info'][$deliveryType]['allow_skip_to']
                                        ){
                                            $result[$index]['ddate_info'][$deliveryType]['allow_skip_to'] = $product->getAllowSkipTo();
                                        }
                                    }
                                }
                            }
                            $result[$index]['ddate_info'][$deliveryType]['allow_skip_date'] = $allowSkipDate;
                        }
                    }
                }
            }
        }

        return $result;
    }
}