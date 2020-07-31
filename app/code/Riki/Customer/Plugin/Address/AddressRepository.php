<?php
namespace Riki\Customer\Plugin\Address;

class AddressRepository 
{

    /**
     * AddressRepository constructor.
     * @param \Riki\Subscription\Model\ProductCart\ResourceModel\ProductCart $productCart
     */
    public function __construct(
        \Riki\Subscription\Model\ProductCart\ResourceModel\ProductCart $productCart
    )
    {
        $this->rikiSubProductCart = $productCart;
    }

    /**
     * @param $subject
     * @param \Magento\Customer\Api\Data\AddressInterface $address
     * @return array
     */
    public function beforeSave($subject, \Magento\Customer\Api\Data\AddressInterface $address){
        $address->setCity('None');
        return [$address];
    }
}