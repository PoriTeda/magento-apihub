<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Test\Constraint;

use Magento\Customer\Test\Page\CustomerAddressEdit;
use Magento\CustomerCustomAttributes\Test\Fixture\CustomerAddressAttribute;
use Magento\Mtf\Constraint\AbstractConstraint;

/**
 * Class AssertCustomerAddressAttributeOnAddressEditPage
 */
class AssertCustomerAddressAttributeOnAddressEditPage extends AbstractConstraint
{
    /**
     * Assert that visible customer address attribute is exist during editing address in customer account
     * on frontend
     *
     * @param CustomerAddressEdit $customerAddressEdit
     * @param CustomerAddressAttribute $customAttribute
     * @return void
     */
    public function processAssert(
        CustomerAddressEdit $customerAddressEdit,
        CustomerAddressAttribute $customAttribute
    ) {
        $attributeCode = $customAttribute->getAttributeCode();
        $isVisible = $customerAddressEdit->getEditForm()->isAddressSimpleAttributeVisible($attributeCode);

        \PHPUnit\Framework\Assert::assertTrue(
            $isVisible,
            'Customer Address Attribute with attribute code: \'' . $attributeCode . '\' '
            . 'is not present during editing address in customer account on frontend.'
        );
    }

    /**
     * Returns a string representation of the object
     *
     * @return string
     */
    public function toString()
    {
        return 'Customer Address Attribute is exist during editing address customer in customer account on frontend.';
    }
}
