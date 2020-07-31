<?php

namespace Bluecom\Paygent\Model\ResourceModel;

class Reauthorize extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('riki_authorization_timing', 'id');
    }


}