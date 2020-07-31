<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PaymentStaging\Test\Unit\Model\Method;

use Magento\Payment\Model\MethodInterface;
use Magento\PaymentStaging\Plugin\Model\Method\PaymentMethodIsAvailable;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Staging\Model\VersionManager;

/**
 * Class PaymentMethodIsAvailableTest
 */
class PaymentMethodIsAvailableTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Result of 'proceed' closure call
     */
    const PROCEED_RESULT = 'proceed';

    /**
     * @var PaymentMethodIsAvailable
     */
    private $plugin;

    /**
     * @var VersionManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $versionManager;

    /**
     * @var MethodInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $subject;

    /**
     * @var \Closure
     */
    private $proceed;

    /**
     * @var CartInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quote;

    /**
     *
     */
    public function setUp()
    {
        $this->versionManager = $this->getMockBuilder(VersionManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->subject = $this->getMockBuilder(MethodInterface::class)
            ->getMockForAbstractClass();

        $this->proceed = function () {
            return self::PROCEED_RESULT;
        };

        $this->quote = $this->getMockBuilder(CartInterface::class)
            ->getMockForAbstractClass();

        $this->plugin = new PaymentMethodIsAvailable($this->versionManager);
    }

    /**
     * Return true for offline payment methods in preview version
     */
    public function testAroundIsAvailableIsPreviewVersionIsOffline()
    {
        $this->versionManager->expects($this->once())
            ->method('isPreviewVersion')
            ->willReturn(true);

        $this->subject->expects($this->once())
            ->method('isOffline')
            ->willReturn(true);

        $this->assertSame(
            self::PROCEED_RESULT,
            $this->plugin->aroundIsAvailable(
                $this->subject,
                $this->proceed,
                $this->quote
            )
        );
    }

    /**
     * Return false for non-offline payment methods in preview version
     */
    public function testAroundIsAvailableIsPreviewVersionNotIsOffline()
    {
        $this->versionManager->expects($this->once())
            ->method('isPreviewVersion')
            ->willReturn(true);

        $this->subject->expects($this->once())
            ->method('isOffline')
            ->willReturn(false);

        $this->assertSame(
            false,
            $this->plugin->aroundIsAvailable(
                $this->subject,
                $this->proceed,
                $this->quote
            )
        );
    }
}
