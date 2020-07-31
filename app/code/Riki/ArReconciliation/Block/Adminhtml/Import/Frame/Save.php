<?php

namespace Riki\ArReconciliation\Block\Adminhtml\Import\Frame;

class Save extends \Magento\Backend\Block\Template
{
    const SUCCESS_TYPE = 1;

    protected $_reponseType;

    public function setReponseType($type)
    {
        $this->_reponseType = $type;
    }

    public function getRedirectUrl()
    {
        if ($this->_reponseType == self::SUCCESS_TYPE) {
            return $this->getUrl('importpaymentcsv/import/index');
        } else {
            return $this->getUrl('importpaymentcsv/import/new');
        }
    }
}
