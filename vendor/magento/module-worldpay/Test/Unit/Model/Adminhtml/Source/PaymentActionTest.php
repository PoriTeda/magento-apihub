<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Worldpay\Test\Unit\Model\Adminhtml\Source;

use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Worldpay\Model\Adminhtml\Source\PaymentAction;

/**
 * Class PaymentActionTest
 *
 * Test for class \Magento\Worldpay\Model\Adminhtml\Source\PaymentAction
 */
class PaymentActionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Run test toOptionArray method
     */
    public function testToOptionArray()
    {
        $paymentAction = new PaymentAction();
        $this->assertEquals(
            [
                [
                    'value' => AbstractMethod::ACTION_AUTHORIZE,
                    'label' => __('Authorize')
                ],
                [
                    'value' => AbstractMethod::ACTION_AUTHORIZE_CAPTURE,
                    'label' => __('Authorize and Capture')
                ]
            ],
            $paymentAction->toOptionArray()
        );
    }
}
