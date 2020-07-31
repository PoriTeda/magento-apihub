<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CheckoutStaging\Test\Unit\Plugin;

use Magento\Checkout\Api\PaymentInformationManagementInterface;
use Magento\CheckoutStaging\Plugin\PaymentInformationManagementPlugin;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Staging\Model\VersionManager;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Class PaymentInformationManagementPluginTest
 */
class PaymentInformationManagementPluginTest extends \PHPUnit\Framework\TestCase
{
    const CART_ID = 1;

    /**
     * @var VersionManager|MockObject
     */
    private $versionManager;

    /**
     * @var PaymentInformationManagementInterface|MockObject
     */
    private $paymentInformationManagement;

    /**
     * @var PaymentInterface|MockObject
     */
    private $paymentMethod;

    /**
     * @var AddressInterface|MockObject
     */
    private $address;

    /**
     * @var PaymentInformationManagementPlugin
     */
    private $plugin;

    protected function setUp()
    {
        $this->versionManager = $this->getMockBuilder(VersionManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['isPreviewVersion'])
            ->getMock();

        $this->paymentInformationManagement = $this->createMock(PaymentInformationManagementInterface::class);
        $this->paymentMethod = $this->createMock(PaymentInterface::class);
        $this->address = $this->createMock(AddressInterface::class);

        $this->plugin = new PaymentInformationManagementPlugin($this->versionManager);
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage The order can't be submitted in preview mode.
     */
    public function testBeforeSavePaymentInformationAndPlaceOrder()
    {
        $this->versionManager->expects(static::once())
            ->method('isPreviewVersion')
            ->willReturn(true);

        $this->plugin->beforeSavePaymentInformationAndPlaceOrder(
            $this->paymentInformationManagement,
            self::CART_ID,
            $this->paymentMethod,
            $this->address
        );
    }
}
