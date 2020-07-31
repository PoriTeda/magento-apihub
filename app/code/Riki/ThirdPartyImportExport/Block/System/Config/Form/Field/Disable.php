<?php
namespace Riki\ThirdPartyImportExport\Block\System\Config\Form\Field;

use Magento\Framework\Data\Form\Element\AbstractElement;
class Disable extends \Magento\Config\Block\System\Config\Form\Field
{

    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $element->setDisabled('disabled');
        $value = $element->getValue();
        if ($value) {
            $element->setValue($this->_localeDate->date($value)->format("Y-m-d H:i:s"));
        } else {
            $element->setValue(null);
        }
        return $element->getElementHtml();
    }
}