<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogStaging\Test\Constraint;

use Magento\Catalog\Test\Page\Adminhtml\CatalogProductEdit;
use Magento\Mtf\Constraint\AbstractConstraint;
use Magento\Catalog\Test\Fixture\CatalogProductSimple;

/**
 * Assert that temporary product update is still visible after activation
 */
class AssertUpdateSavedForProductWithCustomOptions extends AbstractConstraint
{
    /**
     * Assert that update is saved successfully and is visible for product with Custom options
     *
     * @param array $updates
     * @param CatalogProductEdit $catalogProductEdit
     * @param CatalogProductSimple $product
     * @return void
     */
    public function processAssert(
        array $updates,
        CatalogProductEdit $catalogProductEdit,
        CatalogProductSimple $product
    ) {
        $catalogProductEdit->open(['id' => $product->getId()]);

        foreach ($updates as $update) {
            \PHPUnit\Framework\Assert::assertTrue(
                $catalogProductEdit->getProductScheduleBlock()->updateCampaignExists($update->getName()),
                $update->getName() . ' should be visible.'
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
        return 'Scheduled update shows up in the Scheduled changes block.';
    }
}
