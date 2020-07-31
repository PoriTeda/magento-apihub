<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogStaging\Test\Constraint;

use Magento\Catalog\Test\Page\Adminhtml\CatalogProductEdit;
use Magento\Mtf\Constraint\AbstractConstraint;

/**
 * Class AssertDateInvalidErrorMessage
 */
class AssertDateInvalidErrorMessage extends AbstractConstraint
{
    /**
     * Text value to be checked.
     */
    const INVALID_DATE_ERROR_MESSAGE = "error: : The Future Update End Time is invalid. "
    . "It can't be the same time or earlier than the current time.";

    /**
     * Assert that the message is displayed upon saving the product with an invalid date range.
     *
     * @param CatalogProductEdit $productPage
     * @return void
     */
    public function processAssert(CatalogProductEdit $productPage)
    {
        $actualMessage = $productPage->getProductScheduleForm()->getErrorMessage();
        \PHPUnit\Framework\Assert::assertContains(
            self::INVALID_DATE_ERROR_MESSAGE,
            $actualMessage,
            'Wrong error message is displayed.'
            . "\nExpected: " . self::INVALID_DATE_ERROR_MESSAGE
            . "\nActual:\n" . $actualMessage
        );
    }

    /**
     * Returns a string representation of the object
     *
     * @return string
     */
    public function toString()
    {
        return 'Invalid date range error message is displayed.';
    }
}
