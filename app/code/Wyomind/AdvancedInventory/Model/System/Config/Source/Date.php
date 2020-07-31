<?php

namespace Wyomind\AdvancedInventory\Model\System\Config\Source;

use Magento\Framework\Data\Form\Element\AbstractElement;

class Date extends \Magento\Config\Block\System\Config\Form\Field
{

    protected function _getElementHtml(AbstractElement $element)
    {

        $om = \Magento\Framework\App\ObjectManager::getInstance();
        $date = $om->get("\Magento\Framework\Data\Form\Element\Date");
        $format = $this->_localeDate->getDateFormat(
            \IntlDateFormatter::SHORT
        );
        $data = [
            'name' => $element->getName(),
            'html_id' => $element->getId(),
            'image' => $this->getSkinUrl('images/grid-cal.gif'),
        ];
        $date->setData($data);
        $date->setValue($element->getValue(), $format);

        $date->setDateFormat('Y-M-dd');
        $date->setForm($element->getForm());

        return $date->getElementHtml();
    }
}
