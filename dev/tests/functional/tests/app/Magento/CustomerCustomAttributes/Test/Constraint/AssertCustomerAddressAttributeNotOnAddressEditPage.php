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
 * Class AssertCustomerAddressAttributeNotOnAddressEditPage
 */
class AssertCustomerAddressAttributeNotOnAddressEditPage extends AbstractConstraint
{
    /**
     * Assert that not visible customer address attribute is absent during editing address in customer account
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

        \PHPUnit\Framework\Assert::assertFalse(
            $isVisible,
            'Customer Address Attribute with attribute code: \'' . $attributeCode . '\' '
            . 'is present during editing address in customer account on frontend.'
        );
    }

    /**
     * Returns a string representation of the object
     *
     * @return string
     */
    public function toString()
    {
        return 'Customer Address Attribute is absent during editing address customer in customer account on frontend.';
    }
}
