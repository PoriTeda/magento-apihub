<?php
namespace Riki\Promo\Plugin\Promo\Admin;

class UpdateRuleDataObserver
{
    /**
     * @param \Amasty\Promo\Observer\Admin\UpdateRuleDataObserver $subject
     * @param \Magento\Framework\Event\Observer $observer
     * @return \Magento\Framework\Event\Observer
     */
    public function beforeExecute(
        \Amasty\Promo\Observer\Admin\UpdateRuleDataObserver $subject,
        \Magento\Framework\Event\Observer $observer
    ) {
        $action = $observer->getRequest()->getParam('simple_action', 0);
        $ampromoData = $observer->getRequest()->getParam('ampromorule');

        if($action && isset($ampromoData['type'])){

            $visibleInCart = isset($ampromoData['att_visible_cart'])? $ampromoData['att_visible_cart'] : 0;

            switch($action){
                case \Amasty\Promo\Model\Rule::SAME_PRODUCT:
                    break;
                default:
                    if($ampromoData['type']){
                        $visibleInCart = 1;
                    }
            }

            $ampromoData['att_visible_cart'] = $visibleInCart;

            $observer->getRequest()->setParam('ampromorule', $ampromoData);
        }

        return [$observer];
    }
}
