<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Test\Unit\Model\Layout;

/**
 * Class DepersonalizePluginTest
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DepersonalizePluginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CustomerSegment\Model\Layout\DepersonalizePlugin
     */
    protected $plugin;

    /**
     * @var \Magento\Framework\View\LayoutInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $layoutMock;

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $sessionMock;

    /**
     * @var \Magento\Customer\Model\Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $customerSessionMock;

    /**
     * @var \Magento\Customer\Model\CustomerFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $customerFactoryMock;

    /**
     * @var \Magento\Framework\Event\Manager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventManagerMock;

    /**
     * @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestMock;

    /**
     * @var \Magento\Customer\Model\Customer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $customerMock;

    /**
     * @var \Magento\Framework\App\Http\Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $httpContextMock;

    /**
     * @var \Magento\Framework\Module\Manager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $moduleManagerMock;

    /**
     * @var \Magento\PageCache\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cacheConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManagerMock;

    /**
     * SetUp
     */
    protected function setUp()
    {
        $this->httpContextMock = $this->createMock(\Magento\Framework\App\Http\Context::class);
        $this->layoutMock = $this->createMock(\Magento\Framework\View\Layout::class);
        $this->moduleManagerMock = $this->createMock(\Magento\Framework\Module\Manager::class);
        $this->customerSessionMock = $this->createPartialMock(
            \Magento\Customer\Model\Session::class,
            ['getCustomerSegmentIds', 'setCustomerSegmentIds']
        );
        $this->requestMock = $this->createMock(\Magento\Framework\App\Request\Http::class);

        $this->cacheConfig = $this->createMock(\Magento\PageCache\Model\Config::class);

        $this->storeManagerMock = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);

        $this->plugin = new \Magento\CustomerSegment\Model\Layout\DepersonalizePlugin(
            $this->customerSessionMock,
            $this->requestMock,
            $this->moduleManagerMock,
            $this->httpContextMock,
            $this->cacheConfig,
            $this->storeManagerMock
        );
    }

    /**
     * testDepersonalize
     * @dataProvider dataProviderBeforeGenerateXml
     */
    public function testBeforeGenerateXml($isCustomerLoggedIn)
    {
        $websiteId = 1;
        $customerSegmentIds = [1 => [1, 2, 3]];
        $expectedCustomerSegmentIds = [1, 2, 3];
        $defaultCustomerSegmentIds = [];

        if (!$isCustomerLoggedIn) {
            $defaultCustomerSegmentIds = $expectedCustomerSegmentIds;
        }

        $this->moduleManagerMock->expects($this->exactly(2))
            ->method('isEnabled')
            ->with($this->equalTo('Magento_PageCache'))
            ->will($this->returnValue(true));
        $this->cacheConfig->expects($this->exactly(2))
            ->method('isEnabled')
            ->will($this->returnValue(true));
        $this->requestMock->expects($this->exactly(2))
            ->method('isAjax')
            ->will($this->returnValue(false));
        $this->layoutMock->expects($this->exactly(2))
            ->method('isCacheable')
            ->will($this->returnValue(true));
        $this->customerSessionMock->expects($this->once())
            ->method('getCustomerSegmentIds')
            ->will($this->returnValue($customerSegmentIds));
        $this->customerSessionMock->expects($this->once())
            ->method('setCustomerSegmentIds')
            ->with($this->equalTo($customerSegmentIds));
        $websiteMock = $this->createMock(\Magento\Store\Model\Website::class);
        $websiteMock->expects($this->once())
            ->method('getId')
            ->willReturn($websiteId);
        $this->storeManagerMock->expects($this->once())
            ->method('getWebsite')
            ->with(null)
            ->willReturn($websiteMock);

        $this->httpContextMock->expects($this->once())
            ->method('getValue')
            ->with(\Magento\Customer\Model\Context::CONTEXT_AUTH)
            ->willReturn($isCustomerLoggedIn);
        $this->httpContextMock->expects($this->once())
            ->method('setValue')
            ->with(
                $this->equalTo(\Magento\CustomerSegment\Helper\Data::CONTEXT_SEGMENT),
                $this->equalTo($expectedCustomerSegmentIds),
                $this->equalTo($defaultCustomerSegmentIds)
            );

        $this->plugin->beforeGenerateXml($this->layoutMock);
        $result = 'data';
        $this->assertEquals($result, $this->plugin->afterGenerateXml($this->layoutMock, $result));
    }

    /**
     * @return array
     */
    public function dataProviderBeforeGenerateXml()
    {
        return [
            [true],
            [false],
        ];
    }

    public function testBeforeGenerateXmlWithNoWebsite()
    {
        $websiteId = 2;
        $customerSegmentIds = [1 => [1, 2, 3]];
        $expectedCustomerSegmentIds = [];
        $defaultCustomerSegmentIds = [];
        $this->moduleManagerMock->expects($this->exactly(2))
            ->method('isEnabled')
            ->with($this->equalTo('Magento_PageCache'))
            ->will($this->returnValue(true));
        $this->cacheConfig->expects($this->exactly(2))
            ->method('isEnabled')
            ->will($this->returnValue(true));
        $this->requestMock->expects($this->exactly(2))
            ->method('isAjax')
            ->will($this->returnValue(false));
        $this->layoutMock->expects($this->exactly(2))
            ->method('isCacheable')
            ->will($this->returnValue(true));
        $this->customerSessionMock->expects($this->once())
            ->method('getCustomerSegmentIds')
            ->will($this->returnValue($customerSegmentIds));
        $this->customerSessionMock->expects($this->once())
            ->method('setCustomerSegmentIds')
            ->with($this->equalTo($customerSegmentIds));
        $websiteMock = $this->createMock(\Magento\Store\Model\Website::class);
        $websiteMock->expects($this->once())
            ->method('getId')
            ->willReturn($websiteId);
        $this->storeManagerMock->expects($this->once())
            ->method('getWebsite')
            ->with(null)
            ->willReturn($websiteMock);
        $this->httpContextMock->expects($this->once())
            ->method('setValue')
            ->with(
                $this->equalTo(\Magento\CustomerSegment\Helper\Data::CONTEXT_SEGMENT),
                $this->equalTo($expectedCustomerSegmentIds),
                $this->equalTo($defaultCustomerSegmentIds)
            );
        $this->plugin->beforeGenerateXml($this->layoutMock);
        $result = 'data';
        $this->assertEquals($result, $this->plugin->afterGenerateXml($this->layoutMock, $result));
    }

    /**
     * testUsualBehaviorIsAjax
     */
    public function testUsualBehaviorIsAjax()
    {
        $this->moduleManagerMock->expects($this->exactly(2))
            ->method('isEnabled')
            ->with($this->equalTo('Magento_PageCache'))
            ->will($this->returnValue(true));
        $this->cacheConfig->expects($this->exactly(2))
            ->method('isEnabled')
            ->will($this->returnValue(true));
        $this->requestMock->expects($this->exactly(2))
            ->method('isAjax')
            ->will($this->returnValue(true));
        $this->layoutMock->expects($this->never())
            ->method('isCacheable');
        $this->plugin->beforeGenerateXml($this->layoutMock);
        $result = 'data';
        $this->assertEquals($result, $this->plugin->afterGenerateXml($this->layoutMock, $result));
    }

    /**
     * testUsualBehaviorNonCacheable
     */
    public function testUsualBehaviorNonCacheable()
    {
        $this->moduleManagerMock->expects($this->exactly(2))
            ->method('isEnabled')
            ->with($this->equalTo('Magento_PageCache'))
            ->will($this->returnValue(true));
        $this->cacheConfig->expects($this->exactly(2))
            ->method('isEnabled')
            ->will($this->returnValue(true));
        $this->requestMock->expects($this->exactly(2))
            ->method('isAjax')
            ->will($this->returnValue(false));
        $this->layoutMock->expects($this->exactly(2))
            ->method('isCacheable')
            ->will($this->returnValue(false));
        $this->customerSessionMock->expects($this->never())
            ->method('setCustomerSegmentIds');
        $this->plugin->beforeGenerateXml($this->layoutMock);
        $result = 'data';
        $this->assertEquals($result, $this->plugin->afterGenerateXml($this->layoutMock, $result));
    }
}
