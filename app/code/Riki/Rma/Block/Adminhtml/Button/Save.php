<?php
namespace Riki\Rma\Block\Adminhtml\Button;

class Save extends Generic
{
    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getData()
    {
        return [
            'label' => __('Save'),
            'class' => 'save primary'
        ];
    }
}