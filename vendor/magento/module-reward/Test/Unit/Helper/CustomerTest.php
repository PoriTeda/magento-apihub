<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Test\Unit\Helper;

class CustomerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $frontendUrlBuilderMock;

    /**
     * @var \Magento\Reward\Helper\Customer
     */
    protected $subject;

    protected function setUp()
    {
        $this->storeManagerMock = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);
        $contextMock = $this->createMock(\Magento\Framework\App\Helper\Context::class);
        $this->frontendUrlBuilderMock = $this->createMock(\Magento\Framework\Url::class);

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->subject = $objectManagerHelper->getObject(
            \Magento\Reward\Helper\Customer::class,
            [
                'storeManager' => $this->storeManagerMock,
                'context' => $contextMock,
                'frontendUrlBuilder' => $this->frontendUrlBuilderMock
            ]
        );
    }

    public function testGetUnsubscribeUrlIfNotificationDisabled()
    {
        $storeId = 100;
        $url = 'unsubscribe_url';
        $params = ['_nosid' => true, 'store_id' => $storeId];

        $this->frontendUrlBuilderMock->expects($this->once())
            ->method('getUrl')
            ->with('reward/customer/unsubscribe', $params)
            ->willReturn($url);

        $this->frontendUrlBuilderMock->expects($this->once())
            ->method('setScope')->with($storeId)->willReturnSelf();
        $this->assertEquals($url, $this->subject->getUnsubscribeUrl(false, $storeId));
    }

    public function testGetUnsubscribeUrlIfNotificationEnabled()
    {
        $storeId = 100;
        $url = 'unsubscribe_url';
        $params = ['_nosid' => true, 'store_id' => $storeId, 'notification' => true];

        $this->frontendUrlBuilderMock->expects($this->once())
            ->method('getUrl')
            ->with('reward/customer/unsubscribe', $params)
            ->willReturn($url);

        $this->frontendUrlBuilderMock->expects($this->once())
            ->method('setScope')->with($storeId)->willReturnSelf();
        $this->assertEquals($url, $this->subject->getUnsubscribeUrl(true, $storeId));
    }

    public function testGetUnsubscribeUrlIfStoreIdNotSet()
    {
        $url = 'unsubscribe_url';
        $params = ['_nosid' => true, 'notification' => true];

        $this->frontendUrlBuilderMock->expects($this->once())
            ->method('getUrl')
            ->with('reward/customer/unsubscribe', $params)
            ->willReturn($url);

        $this->frontendUrlBuilderMock->expects($this->once())
            ->method('setScope')->with(null)->willReturnSelf();
        $this->assertEquals($url, $this->subject->getUnsubscribeUrl(true));
    }
}
