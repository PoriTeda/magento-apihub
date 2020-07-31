<?php

namespace Riki\Rule\Data\Form\Element;

class Time extends \Magento\Framework\Data\Form\Element\Time
{
    /**
     * Get ElementHtml
     *
     * @return string
     */
    public function getElementHtml()
    {
        $this->addClass('select admin__control-select');

        $valueHrs = 0;
        $valueMin = 0;
        $valueSec = 0;

        if ($value = $this->getValue()) {
            $values = explode(',', $value);
            if (is_array($values) && count($values) == 3) {
                $valueHrs = $values[0];
                $valueMin = $values[1];
                $valueSec = $values[2];
            } else {
                $values = explode(':', $value);
                if (is_array($values) && count($values) == 3) {
                    $valueHrs = $values[0];
                    $valueMin = $values[1];
                    $valueSec = $values[2];
                }
            }
        }

        $html = '<input type="hidden" id="'.$this->getHtmlId().'" '.$this->_getUiId().'/>';
        $html .= '<select name="'.$this->getName().'" style="width:80px" '.$this->serialize(
                $this->getHtmlAttributes()
            ).$this->_getUiId(
                'hour'
            ).'>'."\n";
        for ($i = 0; $i < 24; $i++) {
            $hour = str_pad($i, 2, '0', STR_PAD_LEFT);
            $html .= '<option value="'.$hour.'" '.($valueHrs ==
                $i ? 'selected="selected"' : '').'>'.$hour.'</option>';
        }
        $html .= '</select>'."\n";

        $html .= ':&nbsp;<select name="'.$this->getName().'" style="width:80px" '.$this->serialize(
                $this->getHtmlAttributes()
            ).$this->_getUiId(
                'minute'
            ).'>'."\n";
        for ($i = 0; $i < 60; $i++) {
            $hour = str_pad($i, 2, '0', STR_PAD_LEFT);
            $html .= '<option value="'.$hour.'" '.($valueMin ==
                $i ? 'selected="selected"' : '').'>'.$hour.'</option>';
        }
        $html .= '</select>'."\n";

        $html .= ':&nbsp;<select name="'.$this->getName().'" style="width:80px" '.$this->serialize(
                $this->getHtmlAttributes()
            ).$this->_getUiId(
                'second'
            ).'>'."\n";
        for ($i = 0; $i < 60; $i++) {
            $hour = str_pad($i, 2, '0', STR_PAD_LEFT);
            $html .= '<option value="'.$hour.'" '.($valueSec ==
                $i ? 'selected="selected"' : '').'>'.$hour.'</option>';
        }
        $html .= '</select>'."\n";
        $html .= $this->getAfterElementHtml();

        return $html;
    }
}
