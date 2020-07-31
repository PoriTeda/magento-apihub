<?php
namespace Bluecom\Paygent\Model;

class Error extends \Magento\Framework\Model\AbstractModel
{

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Bluecom\Paygent\Model\ResourceModel\Error');
    }
}