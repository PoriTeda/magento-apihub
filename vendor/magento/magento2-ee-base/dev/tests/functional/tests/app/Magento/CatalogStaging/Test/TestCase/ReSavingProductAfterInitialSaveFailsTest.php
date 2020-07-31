<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogStaging\Test\TestCase;

use Magento\CatalogStaging\Test\Constraint\AssertDateInvalidErrorMessage as AssertDateErrorMessage;
use Magento\Catalog\Test\Page\Adminhtml\CatalogProductEdit;
use Magento\Catalog\Test\Fixture\CatalogProductSimple;
use Magento\Staging\Test\Fixture\Update;
use Magento\Catalog\Test\Page\Adminhtml\CatalogProductIndex;
use Magento\Catalog\Test\Page\Adminhtml\CatalogProductNew;
use Magento\Mtf\Fixture\FixtureFactory;
use Magento\Mtf\TestCase\Injectable;

/**
 * Steps:
 *
 * 1. Login to backend.
 * 2. Create a product
 * 3. Create a scheduled update
 * 4. In the Stagingform, Enter a Start date which is less than the To date
 * 5. Save the staging form
 * 3. Validate error messsage
 * 4. Modify the dates to valid values
 * 5. Save the scheduled update and product again
 * 6. Product is saved successfully
 */
class ReSavingProductAfterInitialSaveFailsTest extends Injectable
{
    /* tags */
    const MVP = 'no';
    /* end tags */

    /**
     * Page to update a product.
     *
     * @var CatalogProductEdit
     */
    private $editProductPage;

    /**
     * Assert Invalid Date error message.
     *
     * @var AssertDateErrorMessage
     */
    private $assertDateErrorMessage;

    /**
     * Injection data.
     *
     * @param CatalogProductEdit $editProductPage
     * @return void
     */
    public function __inject(
        CatalogProductEdit $editProductPage,
        AssertDateErrorMessage $assertDateErrorMessage
    ) {
        $this->editProductPage = $editProductPage;
        $this->assertDateErrorMessage = $assertDateErrorMessage;
    }

    /**
     * @param CatalogProductSimple $product
     * @param Update $update1
     * @param Update $update2
     */
    public function test(CatalogProductSimple $product, Update $update1, Update $update2)
    {
        // Preconditions
        $product->persist();

        // Test steps
        $this->editProductPage->open(['id' => $product->getId()]);
        $this->editProductPage->getProductScheduleBlock()->clickScheduleNewUpdate();
        $this->editProductPage->getProductScheduleForm()->fill($update1);
        $this->editProductPage->getStagingFormPageActions()->save();
        $this->assertDateErrorMessage->processAssert($this->editProductPage);
        $this->editProductPage->getProductScheduleForm()->fill($update2);
        $this->editProductPage->getStagingFormPageActions()->save();
        $this->editProductPage->getFormPageActions()->save();
    }
}
