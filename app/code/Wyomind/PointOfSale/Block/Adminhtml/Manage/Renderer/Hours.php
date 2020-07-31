<?php

namespace Wyomind\PointOfSale\Block\Adminhtml\Manage\Renderer;

class Hours extends \Magento\Framework\Data\Form\Element\AbstractElement
{

    /**
     * Retrieve allow attributes
     *
     * @return array
     */
    public function getHtmlAttributes()
    {
        return ['type', 'name', 'class', 'style', 'checked', 'onclick', 'onchange', 'disabled'];
    }

    /**
     * Prepare value list
     *
     * @return array
     */
    protected function _prepareValues()
    {
        $values = [
            [
                'value' => "Monday",
                'label' => __('Monday'),
            ],
            [
                'value' => "Tuesday",
                'label' => __('Tuesday'),
            ],
            [
                'value' => "Wednesday",
                'label' => __('Wednesday'),
            ],
            [
                'value' => "Thursday",
                'label' => __('Thursday'),
            ],
            [
                'value' => "Friday",
                'label' => __('Friday'),
            ],
            [
                'value' => "Saturday",
                'label' => __('Saturday'),
            ],
            [
                'value' => "Sunday",
                'label' => __('Sunday'),
            ],
        ];

        return $values;
    }

    /**
     * Retrieve HTML
     *
     * @return string
     */
    public function getElementHtml()
    {

        $values = $this->_prepareValues();

        if (!$values) {
            return '';
        }
        $id = $this->getHtmlId();

        $html = "<script language='javascript'>var elementId='" . $id . "';</script>";

        $html.= '<ul class="checkboxes">';


        foreach ($values as $day) {
            $html.='<li style="display:inline-block;width:300px;float:left">';
            $html .= '<label class="data-grid-checkbox-cell-inner">'
                    . '<input value="' . $day['value'] . '" '
                    . 'class="' . $id . '_day admin__control-checkbox" '
                    . 'id="' . $day['value'] . '" '
                    . 'onclick="PointOfSale.activeField(this,\'' . $id . '\')" '
                    . 'type="checkbox" '
                    . 'value="' . $day['value'] . '" />'
                    . '<label for="' . $day['value'] . '">&nbsp;<b>' . $day['label'] . '</b></label>'
                    . '</label>';

            $html.="<div style='margin:4px 0 2px 35px;'> <select style='width:100px;' id='" . $day['value'] . "_open' onchange='PointOfSale.summary(\"$id\")'>";
            for ($h = 0; $h <= 24; $h++) {
                for ($m = 0; $m < 60; $m = $m + 15) {
                    $html.="<option value='" . str_pad($h, 2, 0, STR_PAD_LEFT) . ':' . str_pad($m, 2, 0, STR_PAD_LEFT) . "'>" . str_pad($h, 2, 0, STR_PAD_LEFT) . ':' . str_pad($m, 2, 0, STR_PAD_LEFT) . "</option>";
                    if ($h == 24) {
                        break;
                    }
                }
            }
            $html.="</select> - ";
            $html.="<select style='width:100px;' id='" . $day['value'] . "_close' onchange='PointOfSale.summary(\"$id\")'>";
            for ($h = 0; $h <= 24; $h++) {
                for ($m = 0; $m < 60; $m = $m + 15) {
                    $html.="<option value='" . str_pad($h, 2, 0, STR_PAD_LEFT) . ':' . str_pad($m, 2, 0, STR_PAD_LEFT) . "'>" . str_pad($h, 2, 0, STR_PAD_LEFT) . ':' . str_pad($m, 2, 0, STR_PAD_LEFT) . "</option>";
                    if ($h == 24) {
                        break;
                    }
                }
            }
            $html.="</select></div>";
            $html.='</li>';
            
            
            
            $html.='<li style="display:inline-block;width:300px;float:left">';
            $html .= '<label class="data-grid-checkbox-cell-inner">'
                    . '<input value="' . $day['value'] . '" '
                    . 'class="' . $id . '_lunch admin__control-checkbox" '
                    . 'id="' . $day['value'] . '_lunch" '
                    . 'onclick="PointOfSale.activeFieldLunch(this,\'' . $id . '\')" '
                    . 'type="checkbox" '
                    . 'value="' . $day['value'] . '_lunch" />'
                    . '<label for="' . $day['value'] . '_lunch">&nbsp;<b>' . __("Lunch hours") . '</b></label>'
                    . '</label>';

            $html.="<div style='margin:4px 0 2px 35px;'> <select style='width:100px;' id='" . $day['value'] . "_lunch_open' onchange='PointOfSale.summary(\"$id\")'>";
            for ($h = 0; $h <= 24; $h++) {
                for ($m = 0; $m < 60; $m = $m + 15) {
                    $html.="<option value='" . str_pad($h, 2, 0, STR_PAD_LEFT) . ':' . str_pad($m, 2, 0, STR_PAD_LEFT) . "'>" . str_pad($h, 2, 0, STR_PAD_LEFT) . ':' . str_pad($m, 2, 0, STR_PAD_LEFT) . "</option>";
                    if ($h == 24) {
                        break;
                    }
                }
            }
            $html.="</select> - ";
            $html.="<select style='width:100px;' id='" . $day['value'] . "_lunch_close' onchange='PointOfSale.summary(\"$id\")'>";
            for ($h = 0; $h <= 24; $h++) {
                for ($m = 0; $m < 60; $m = $m + 15) {
                    $html.="<option value='" . str_pad($h, 2, 0, STR_PAD_LEFT) . ':' . str_pad($m, 2, 0, STR_PAD_LEFT) . "'>" . str_pad($h, 2, 0, STR_PAD_LEFT) . ':' . str_pad($m, 2, 0, STR_PAD_LEFT) . "</option>";
                    if ($h == 24) {
                        break;
                    }
                }
            }
            $html.="</select></div>";
            $html.='</li>';
        }
        $html .= '</ul>'
                . $this->getAfterElementHtml();

        return $html;
    }
}
