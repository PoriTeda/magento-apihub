<?php
namespace Bluecom\Paygent\Model;

class Reauthorize extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        $this->_init('Bluecom\Paygent\Model\ResourceModel\Reauthorize');
    }
}