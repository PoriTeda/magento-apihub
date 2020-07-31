<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Worldpay\Test\Unit\Model\Adminhtml\Source;

use Magento\Worldpay\Model\Adminhtml\Source\TestAction;

/**
 * Class TestActionTest
 *
 * Test for class \Magento\Worldpay\Model\Adminhtml\Source\TestAction
 */
class TestActionTest extends \PHPUnit\Framework\TestCase
{
    const REFUSED = 'REFUSED';

    const AUTHORISED = 'AUTHORISED';

    const ERROR = 'ERROR';

    const CAPTURED = 'CAPTURED';

    /**
     * Run test toOptionArray method
     */
    public function testToOptionArray()
    {
        $testAction = new TestAction();
        $this->assertEquals(
            [
                [
                    'value' => self::REFUSED,
                    'label' => __('Refused')
                ],
                [
                    'value' => self::AUTHORISED,
                    'label' => __('Authorised')
                ],
                [
                    'value' => self::ERROR,
                    'label' => __('Error')
                ],
                [
                    'value' => self::CAPTURED,
                    'label' => __('Captured')
                ],
            ],
            $testAction->toOptionArray()
        );
    }
}
