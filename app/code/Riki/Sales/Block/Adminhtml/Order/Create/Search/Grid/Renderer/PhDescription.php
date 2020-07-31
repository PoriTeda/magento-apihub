<?php

namespace Riki\Sales\Block\Adminhtml\Order\Create\Search\Grid\Renderer;


class PhDescription extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    public function render(\Magento\Framework\DataObject $row)
    {
        $selectedItems = explode(',', $row->getData('ph5_description'));
        $html = '';
        if($row->getData('ph5_description') != '' && count($selectedItems)>0)
        {
            $options = $this->getColumn()->getOptions();
            foreach($selectedItems as $value)
            {
                if(array_key_exists($value, $options))
                    $html .= $options[$value].'</br>';
            }
        }
        return $html ;
    }
}
