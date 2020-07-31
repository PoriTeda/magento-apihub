<?php

namespace Riki\SubscriptionCutOffEmail\Model\ResourceModel;

class EmailCutOffDate extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('riki_send_mail_cut_off_date', 'id');
    }
}