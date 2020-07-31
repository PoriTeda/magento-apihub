<?php
namespace Riki\ThirdPartyImportExport\Block\Adminhtml\Order\View\Tab;

class Info extends \Magento\Backend\Block\Widget implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * Get label
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Information');
    }

    /**
     * Get title
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Order Information');
    }

    /**
     * check show tab?
     *
     * @return bool
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * check hidden?
     *
     * @return bool
     */
    public function isHidden()
    {
        return false;
    }
}
