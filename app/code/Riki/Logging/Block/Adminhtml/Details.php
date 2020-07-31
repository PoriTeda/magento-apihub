<?php
namespace Riki\Logging\Block\Adminhtml;
class Details extends \Magento\Logging\Block\Adminhtml\Details
{
    /**
     * get session hash
     *
     * @return string|bool
     */
    public function getSessionHash()
    {
        if ($this->getCurrentEvent()) {
            return $this->getCurrentEvent()->getSessionHash();
        }
        return false;
    }
    protected function _toHtml()
    {
        $this->setModuleName($this->extractModuleName('Magento\Logging\Block\Adminhtml\Details'));
        return parent::_toHtml();
    }
}