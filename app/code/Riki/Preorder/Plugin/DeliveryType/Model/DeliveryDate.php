<?php

namespace Riki\Preorder\Plugin\DeliveryType\Model;

class DeliveryDate
{
    protected $_helper;

    /**
     * @param \Riki\Preorder\Helper\Admin $helper
     */
    public function __construct(
        \Riki\Preorder\Helper\Admin $helper
    ){
        $this->_helper = $helper;
    }

    /**
     * @param \Riki\DeliveryType\Model\DeliveryDate $subject
     * @param $listType
     * @return array
     */
    public function beforeGetNameGroup(
        \Riki\DeliveryType\Model\DeliveryDate $subject,
        $listType
    ){
        if($this->_helper->isPreOrderCart()){
            return [
                [$this->_helper->getQuote()->getItemsCollection()->getFirstItem()->getDeliveryType()]
            ];
        }

        return [$listType];
    }
}