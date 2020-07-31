<?php
namespace Riki\Rma\Block\Widget\Grid\Column\Filter;

class Multiple extends \Magento\Backend\Block\Widget\Grid\Column\Filter\Select
{
    /**
     * {@inheritdoc}
     */
    public function getHtml()
    {
        $html = '<select class="multiselectfilter" multiple="true" size="5" name="' . $this->_getHtmlName() . '" id="' . $this->_getHtmlId() . '"' . $this->getUiId(
                'filter',
                $this->_getHtmlName()
            ) . 'class="no-changes admin__control-select">';
        $value = $this->getValue();
        $selectedValues = explode(',', $value);
        foreach ($this->_getOptions() as $option) {
            $selected=0;
            if($option['value'])
            {
                foreach($selectedValues as $_sel)
                {
                    if($option['value'] ==$_sel)
                    {
                        $selected = $_sel;
                    }
                }

                if (is_array($option['value'])) {
                    $html .= '<optgroup label="' . $this->escapeHtml($option['label']) . '">';
                    foreach ($option['value'] as $subOption) {
                        $html .= $this->_renderOption($subOption, $selected);
                    }
                    $html .= '</optgroup>';
                } else {
                    $html .= $this->_renderOption($option, $selected);
                }
            }
        }
        $html .= '</select>';
        return $html;
    }
}