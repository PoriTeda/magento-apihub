<?php
namespace Riki\Rma\Plugin\Rma\Block\Adminhtml\Rma\NewRma\Tab;

class Items
{
    /**
     * Extend setForm()
     *
     * @param \Magento\Rma\Block\Adminhtml\Rma\NewRma\Tab\Items $subject
     * @param \Magento\Framework\Data\Form $form
     *
     * @return array
     */
    public function beforeSetForm(\Magento\Rma\Block\Adminhtml\Rma\NewRma\Tab\Items $subject, \Magento\Framework\Data\Form $form)
    {
        $fieldSet = $form->getElement('rma_item_fields');
        if ($fieldSet instanceof \Magento\Framework\Data\Form\Element\Fieldset) {
            $fieldSet->getElements()->remove('reason_other');
            $fieldSet->getElements()->remove('reason');
            $fieldSet->getElements()->remove('condition');
            $fieldSet->getElements()->remove('resolution');
            $fieldSet->getElements()->remove('add_detail_link');
            $fieldSet->addField('unit_case', 'text', [
                'label' => __('Unit'),
                'name' => 'unit_case',
                'required' => false,
            ], 'qty_ordered');
        }

        return [$form];
    }
}
