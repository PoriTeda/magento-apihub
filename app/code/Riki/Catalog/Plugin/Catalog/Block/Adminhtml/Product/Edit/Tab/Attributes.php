<?php
namespace Riki\Catalog\Plugin\Catalog\Block\Adminhtml\Product\Edit\Tab;

class Attributes
{
    /**
     * @param \Magento\Catalog\Block\Adminhtml\Product\Edit\Tab\Attributes $subject
     * @param \Magento\Catalog\Block\Adminhtml\Product\Edit\Tab\Attributes $result
     * @return \Magento\Catalog\Block\Adminhtml\Product\Edit\Tab\Attributes
     */
    public function afterSetForm(
        \Magento\Catalog\Block\Adminhtml\Product\Edit\Tab\Attributes $subject,
        \Magento\Catalog\Block\Adminhtml\Product\Edit\Tab\Attributes $result
    ) {

        $fieldIds = [
            'booking_item_wbs',
            'booking_point_wbs',
            'booking_free_wbs'
        ];

        $form = $subject->getForm();

        foreach ($fieldIds as $fieldId) {
            $field = $form->getElement($fieldId);
            if ($field) {
                $field->addClass('validate-wbs-code');
            }
        }

        return $result;
    }
}
