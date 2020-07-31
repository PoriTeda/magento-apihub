<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerCustomAttributes\Test\Constraint;

use Magento\Customer\Test\Page\CustomerAddressEdit;
use Magento\Mtf\Constraint\AbstractConstraint;

/**
 * Check fields statuses (visible/not visible) for system customer address attributes.
 */
class AssertCustomerAddressAttributesVisibilityOnFrontend extends AbstractConstraint
{
    /**
     * Assert that system customer address attributes are visible/not visible by default
     *
     * @param CustomerAddressEdit $customerAddressEdit
     * @param array $addressAttributes
     * @return void
     */
    public function processAssert(
        CustomerAddressEdit $customerAddressEdit,
        array $addressAttributes
    ) {
        foreach ($addressAttributes as $code => $value) {
            $isVisible = $customerAddressEdit->getEditForm()->isAddressSimpleAttributeVisible($code);

            \PHPUnit\Framework\Assert::assertEquals(
                $value['is_visible'],
                $isVisible,
                'Customer Address Attribute with attribute code: \'' . $code . '\' '
                . 'has wrong visibility during editing address in customer account on frontend.'
            );
        }
    }

    /**
     * Returns a string representation of the object.
     *
     * @return string
     */
    public function toString()
    {
        return 'Customer address system attributes fields are visible/not visible according to the specified data.';
    }
}
