<?php

namespace Riki\RmaWithoutGoods\Plugin\Rma;

class GetItemsUnsetItems
{
    public function beforeGetItems($subject)
    {
        if ($subject->getIsWithoutGoods()) {
            $subject->setItems([]);
        }
        return [];
    }
}
