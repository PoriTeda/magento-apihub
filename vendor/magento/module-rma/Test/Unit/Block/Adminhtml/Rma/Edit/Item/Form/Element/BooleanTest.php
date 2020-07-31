<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Rma\Test\Unit\Block\Adminhtml\Rma\Edit\Item\Form\Element;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Rma\Block\Adminhtml\Rma\Edit\Item\Form\Element\Boolean;

/**
 * Test class for Magento\Rma\Block\Adminhtml\Rma\Edit\Item\Form\Element\Boolean.
 */
class BooleanTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Boolean
     */
    private $booleanItem;

    protected function setUp()
    {
        $objectManager = new ObjectManager($this);
        $this->booleanItem = $objectManager->getObject(Boolean::class);
    }

    public function testConstruct()
    {
        $expectedValues = [
            [
                'label' => __('No'),
                'value' => 0,
            ],
            [
                'label' => __('Yes'),
                'value' => 1,
            ],
        ];

        $this->assertEquals($expectedValues, $this->booleanItem->getValues());
    }
}
