<?php
namespace Riki\Rma\Block\Adminhtml\System\Config\Form\Field\MassActionCondition;

class FullPartial extends FieldAbstract
{
    /**
     * @return array
     */
    protected function _getOptions()
    {
        if (!$this->options) {
            $this->options = [
                [
                    'label' =>  __('Partial'),
                    'value' =>  'partial'
                ],
                [
                    'label' =>  __('Full'),
                    'value' =>  'full'
                ]
            ];
        }
        return $this->options;
    }
}
