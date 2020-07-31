<?php
namespace Bluecom\Paygent\Block\Adminhtml\Button;

class Save extends Generic
{
    public function getData()
    {
        return [
            'label' => __('Save'),
            'class' => 'save primary'
        ];
    }
}