<?php
namespace Riki\Subscription\Block\Email;
class ProductDisableOrRemoved extends \Magento\Sales\Block\Items\AbstractItems
{
    public function getProduct(){
        return $this->hasData('product') ? $this->getData('product') : [];
    }

}