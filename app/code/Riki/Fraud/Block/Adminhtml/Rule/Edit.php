<?php
namespace Riki\Fraud\Block\Adminhtml\Rule;
class Edit extends \Magento\Framework\View\Element\Template
{
    const REJECT_STATUS = 'reject';
    public function _prepareLayout(){
        $this->setTemplate('Riki_Fraud::rule/edit/edit.phtml');
    }
    public function getRejectStatus()
    {
        return self::REJECT_STATUS;
    }
}