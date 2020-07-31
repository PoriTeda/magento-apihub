<?php
namespace Riki\Customer\Plugin\Block\Address;

class Edit
{
    /**
     * Return the Url for confirm save.
     *
     * @return string
     */
    public function aftergetSaveUrl(\Magento\Customer\Block\Address\Edit $edit, $result)
    {

        if($edit->getData("riki_do_not_use_after")) {
            return $result;
        }

        return $edit->getUrl(
            'customer/address/confirmPost',
            ['_secure' => true, 'id' => $edit->getAddress()->getId()]
        );
    }

}