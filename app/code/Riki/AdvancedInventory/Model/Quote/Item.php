<?php
namespace Riki\AdvancedInventory\Model\Quote;

class Item extends \Magento\Quote\Model\Quote\Item
{
    public function beforeSave()
    {
        $this->setIsVirtual($this->getProduct()->getIsVirtual());
        return $this;
    }
}