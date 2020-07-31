<?php

namespace Riki\Preorder\Plugin;

class CatalogInventoryBackorders
{
    /**
     * @return array
     */
    public function afterToOptionArray(
        \Magento\CatalogInventory\Model\Source\Backorders $subject,
        array $optionArray
    )
    {
        $optionArray[] = [
            'value' => \Riki\Preorder\Helper\Data::BACKORDERS_PREORDER_OPTION,
            'label'=> __('Allow Pre-Orders')
        ];
        return $optionArray;
    }
}
