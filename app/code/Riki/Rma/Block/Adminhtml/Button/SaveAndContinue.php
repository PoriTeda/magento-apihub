<?php
namespace Riki\Rma\Block\Adminhtml\Button;

class SaveAndContinue extends Generic
{
    /**
     * {@inheritdoc}
     *
     * @return mixed[]
     */
    public function getData()
    {
        return [
            'label' => __('Save and Continue Edit'),
            'class' => 'save',
            'on_click' => '',
            'sort_order' => 80
        ];
    }
}
