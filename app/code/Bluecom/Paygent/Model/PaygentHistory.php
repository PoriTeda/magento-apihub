<?php
namespace Bluecom\Paygent\Model;

class PaygentHistory extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        $this->_init('Bluecom\Paygent\Model\ResourceModel\PaygentHistory');
    }
}