<?php
namespace Riki\Chirashi\Plugin\Promo\Model;

class Registry
{

    /**
     * chirashi always be set qty to 1
     */
    public function afterGetPromoItems(
        \Amasty\Promo\Model\Registry $subject,
        $result
    ) {

        if(is_array($result)){
            foreach($result as $key => $giftData){
                if(
                    $key != '_groups' &&
                    isset($giftData['is_chirashi']) &&
                    $giftData['is_chirashi']
                ){
                    $result[$key]['qty'] = isset($giftData['unit_qty'])? $giftData['unit_qty'] : 1;
                }
            }
        }

        return $result;
    }
}
