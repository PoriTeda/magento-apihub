<?php
namespace Riki\Promo\Plugin\Riki\Checkout\Model;

class DefaultConfigProvider
{
    protected $_promoDataHelper;

    /**
     * @param \Riki\Promo\Helper\Data $dataHelper
     */
    public function __construct(
        \Riki\Promo\Helper\Data $dataHelper
    ){
        $this->_promoDataHelper = $dataHelper;
    }

    /**
     * @param \Riki\Checkout\Model\DefaultConfigProvider $subject
     * @param array $result
     * @return array
     */
    public function afterGetQuoteItemData(
        \Riki\Checkout\Model\DefaultConfigProvider $subject,
        array $result
    ){
        foreach($result as $index   =>  $quoteItemData){

            $visibleInCart = isset($quoteItemData['visibleInCart'])? $quoteItemData['visibleInCart'] : 1;

            if ($visibleInCart) {
                if(isset($quoteItemData['ampromo_rule_id'])){
                    $result[$index]['visibleInCart'] = $this->_promoDataHelper->isFreeGiftVisibleInCart($quoteItemData['ampromo_rule_id']);
                }
            }
        }

        return $result;
    }
}
