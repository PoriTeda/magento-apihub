<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Test\Unit\Model;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CustomerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CustomerSegment\Model\Customer
     */
    private $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $_registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $_customerSession;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $_resource;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $collectionFactoryMock;

    /**
     * @var \Magento\Customer\Model\Visitor|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $visitorMock;

    /**
     * @var \Magento\Framework\App\Http\Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $httpContextMock;

    /**
     * @var array
     */
    private $_fixtureSegmentIds = [123, 456];

    protected function setUp()
    {
        $this->_registry = $this->createPartialMock(\Magento\Framework\Registry::class, ['registry']);

        $website = new \Magento\Framework\DataObject(['id' => 5]);
        $storeManager = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);
        $storeManager->expects($this->any())->method('getWebsite')->will($this->returnValue($website));

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $constructArguments = $objectManager->getConstructArguments(
            \Magento\Customer\Model\Session::class,
            ['storage' => new \Magento\Framework\Session\Storage()]
        );
        $this->_customerSession = $this->getMockBuilder(\Magento\Customer\Model\Session::class)
            ->setMethods(['getCustomer', 'getCustomerSegmentIds', 'setCustomerSegmentIds'])
            ->setConstructorArgs($constructArguments)
            ->getMock();

        $contextMock = $this->createMock(\Magento\Framework\Model\ResourceModel\Db\Context::class);
        $contextMock->expects($this->once())
            ->method('getResources')
            ->willReturn($this->createMock(\Magento\Framework\App\ResourceConnection::class));
        $this->_resource = $this->getMockBuilder(\Magento\CustomerSegment\Model\ResourceModel\Customer::class)
            ->setMethods(['getCustomerWebsiteSegments', 'getIdFieldName', 'addCustomerToWebsiteSegments'])
            ->setConstructorArgs(
                [
                    $contextMock,
                    $this->createMock(\Magento\Framework\Stdlib\DateTime::class)
                ]
            )
            ->getMock();
        $this->collectionFactoryMock = $this->createPartialMock(
            \Magento\CustomerSegment\Model\ResourceModel\Segment\CollectionFactory::class,
            ['create']
        );
        $this->visitorMock = $this->createMock(\Magento\Customer\Model\Visitor::class);

        $this->httpContextMock = $this->createMock(\Magento\Framework\App\Http\Context::class);

        $helper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->model = $helper->getObject(
            \Magento\CustomerSegment\Model\Customer::class,
            [
                'registry' => $this->_registry,
                'resource' => $this->_resource,
                'resourceCustomer' => $this->createMock(\Magento\Customer\Model\ResourceModel\Customer::class),
                'visitor' => $this->visitorMock,
                'storeManager' => $storeManager,
                'customerSession' => $this->_customerSession,
                'httpContext' => $this->httpContextMock,
                'collectionFactory' => $this->collectionFactoryMock
            ]
        );
    }

    protected function tearDown()
    {
        $this->model = null;
        $this->_registry = null;
        $this->_customerSession = null;
        $this->_resource = null;
    }

    public function testGetCurrentCustomerSegmentIdsCustomerInRegistry()
    {
        $customer = new \Magento\Framework\DataObject(['id' => 100500]);
        $this->_registry->expects(
            $this->once()
        )->method(
            'registry'
        )->with(
            'segment_customer'
        )->will(
            $this->returnValue($customer)
        );
        $this->_resource->expects(
            $this->once()
        )->method(
            'getCustomerWebsiteSegments'
        )->with(
            100500,
            5
        )->will(
            $this->returnValue($this->_fixtureSegmentIds)
        );
        $this->assertEquals($this->_fixtureSegmentIds, $this->model->getCurrentCustomerSegmentIds());
    }

    /**
     * @return void
     */
    public function testGetCurrentCustomerSegmentIdsCustomerInRegistryNoId(): void
    {
        $customer = new \Magento\Framework\DataObject();
        $this->_registry->expects($this->once())
            ->method('registry')
            ->with('segment_customer')
            ->will($this->returnValue($customer));

        $this->_customerSession->expects($this->once())
            ->method('getCustomerSegmentIds')
            ->willReturn([5 => $this->_fixtureSegmentIds]);

        $this->assertEquals($this->_fixtureSegmentIds, $this->model->getCurrentCustomerSegmentIds());
    }

    public function testGetCurrentCustomerSegmentIdsCustomerInSession()
    {
        $customer = new \Magento\Framework\DataObject(['id' => 100500]);
        $this->_customerSession->expects($this->once())->method('getCustomer')->will($this->returnValue($customer));
        $this->_resource->expects(
            $this->once()
        )->method(
            'getCustomerWebsiteSegments'
        )->with(
            100500,
            5
        )->will(
            $this->returnValue($this->_fixtureSegmentIds)
        );
        $this->assertEquals($this->_fixtureSegmentIds, $this->model->getCurrentCustomerSegmentIds());
    }

    /**
     * @return void
     */
    public function testGetCurrentCustomerSegmentIdsCustomerInSessionNoId(): void
    {
        $customer = new \Magento\Framework\DataObject();
        $this->_customerSession->expects($this->once())
            ->method('getCustomer')
            ->willReturn($customer);
        $this->_customerSession->expects($this->once())
            ->method('getCustomerSegmentIds')
            ->willReturn([5 => $this->_fixtureSegmentIds]);
        $this->assertEquals($this->_fixtureSegmentIds, $this->model->getCurrentCustomerSegmentIds());
    }

    public function testProcessEventForVisitor()
    {
        $event = 'test_event';
        $customerSegment = $this->createPartialMock(
            \Magento\CustomerSegment\Model\Segment::class,
            ['validateCustomer']
        );
        $customerSegment->expects($this->once())->method('validateCustomer')->willReturn(true);
        $customerSegment->setData('apply_to', \Magento\CustomerSegment\Model\Segment::APPLY_TO_VISITORS);
        $customerSegment->setData('id', 'segment_id');

        $segmentCollection = $this->createMock(\Magento\CustomerSegment\Model\ResourceModel\Segment\Collection::class);
        $segmentCollection->expects($this->once())->method('addEventFilter')->with($event)->willReturnSelf();
        $segmentCollection->expects($this->once())->method('addWebsiteFilter')->with(5)->willReturnSelf();
        $segmentCollection->expects($this->once())->method('addIsActiveFilter')->with(1)->willReturnSelf();
        $segmentCollection->expects($this->once())->method('getIterator')->willReturn(
            new \ArrayIterator([$customerSegment])
        );
        $this->collectionFactoryMock->expects($this->once())->method('create')->willReturn($segmentCollection);

        $this->visitorMock->setData('id', 'visitor_1');
        $this->visitorMock->setData('quote_id', 'quote_1');

        $this->assertEquals($this->model, $this->model->processEvent($event, null, 1));
    }

    /**
     * @param mixed $visitorSegmentIds
     * @param int $websiteId
     * @param array $segmentIds
     * @param array $resultSegmentIds
     * @param array $contextSegmentIds
     *
     * @dataProvider dataProviderAddVisitorToWebsiteSegments
     */
    public function testAddVisitorToWebsiteSegments(
        $visitorSegmentIds,
        $websiteId,
        array $segmentIds,
        array $resultSegmentIds,
        array $contextSegmentIds
    ) {
        /**
         * @var \Magento\Framework\Session\SessionManagerInterface|\PHPUnit_Framework_MockObject_MockObject $sessionMock
         */
        $sessionMock = $this->getMockBuilder(\Magento\Framework\Session\SessionManagerInterface::class)
            ->setMethods(['getCustomerSegmentIds', 'setCustomerSegmentIds'])
            ->getMockForAbstractClass();
        $sessionMock->expects($this->once())
            ->method('getCustomerSegmentIds')
            ->willReturn($visitorSegmentIds);
        $sessionMock->expects($this->once())
            ->method('setCustomerSegmentIds')
            ->with($resultSegmentIds);

        $this->httpContextMock->expects($this->once())
            ->method('setValue')
            ->with(\Magento\CustomerSegment\Helper\Data::CONTEXT_SEGMENT, $contextSegmentIds, $contextSegmentIds)
            ->willReturnSelf();

        $this->assertEquals(
            $this->model,
            $this->model->addVisitorToWebsiteSegments($sessionMock, $websiteId, $segmentIds)
        );
    }

    public function dataProviderAddVisitorToWebsiteSegments()
    {
        return [
            ['', 1, [], [1 => []], []],
            [[1 => [2, 3], 2 => [4]], 1, [2, 5], [1 => [2, 3, 3 => 5], 2 => [4]], [2, 3, 3 => 5]],
            [[1 => [2, 3], 3 => [4]], 2, [2, 5], [1 => [2, 3], 2 => [2, 5], 3 => [4]], [2, 5]],
            [[2 => [2, 3]], 2, [], [2 => [2, 3]], [2, 3]],
        ];
    }

    /**
     * @param mixed $visitorSegmentIds
     * @param int $websiteId
     * @param array $segmentIds
     * @param array $resultSegmentIds
     * @param array $contextSegmentIds
     *
     * @dataProvider dataProviderRemoveVisitorFromWebsiteSegments
     */
    public function testRemoveVisitorFromWebsiteSegments(
        $visitorSegmentIds,
        $websiteId,
        array $segmentIds,
        array $resultSegmentIds,
        array $contextSegmentIds
    ) {
        /**
         * @var \Magento\Framework\Session\SessionManagerInterface|\PHPUnit_Framework_MockObject_MockObject $sessionMock
         */
        $sessionMock = $this->getMockBuilder(\Magento\Framework\Session\SessionManagerInterface::class)
            ->setMethods(['getCustomerSegmentIds', 'setCustomerSegmentIds'])
            ->getMockForAbstractClass();
        $sessionMock->expects($this->once())
            ->method('getCustomerSegmentIds')
            ->willReturn($visitorSegmentIds);
        $sessionMock->expects($this->once())
            ->method('setCustomerSegmentIds')
            ->with($resultSegmentIds);

        $this->httpContextMock->expects($this->once())
            ->method('setValue')
            ->with(\Magento\CustomerSegment\Helper\Data::CONTEXT_SEGMENT, $contextSegmentIds, $contextSegmentIds)
            ->willReturnSelf();

        $this->assertEquals(
            $this->model,
            $this->model->removeVisitorFromWebsiteSegments($sessionMock, $websiteId, $segmentIds)
        );
    }

    /**
     * @param array $segmentIds
     * @param array $existingSegmentIds
     * @param array $value
     * @param array $visitorCustomerSegmentIds
     * @param array $resultCustomerSegmentIds
     *
     * @dataProvider dataProviderAddCustomerToWebsiteSegments
     *
     * @return void
     */
    public function testAddCustomerToWebsiteSegments(
        array $segmentIds,
        array $existingSegmentIds,
        array $value,
        array $visitorCustomerSegmentIds,
        array $resultCustomerSegmentIds
    ): void {
        $customerId = 5;
        $websiteId = 1;

        $this->_resource->expects($this->once())
            ->method('getCustomerWebsiteSegments')
            ->with($customerId, $websiteId)
            ->willReturn($existingSegmentIds);

        $this->_resource->expects($this->once())
            ->method('addCustomerToWebsiteSegments')
            ->with($customerId, $websiteId, $segmentIds)
            ->willReturnSelf();

        $this->httpContextMock->expects($this->once())
            ->method('setValue')
            ->with(\Magento\CustomerSegment\Helper\Data::CONTEXT_SEGMENT, $value, $value)
            ->willReturnSelf();

        $this->_customerSession->expects($this->any())
            ->method('getCustomerSegmentIds')
            ->willReturn($visitorCustomerSegmentIds);

        $this->_customerSession->expects($this->any())
            ->method('setCustomerSegmentIds')
            ->with($resultCustomerSegmentIds)
            ->willReturnSelf();

        $this->model->addCustomerToWebsiteSegments($customerId, $websiteId, $segmentIds);
    }
    
    public function dataProviderRemoveVisitorFromWebsiteSegments()
    {
        return [
            ['', 1, [], [], []],
            [[1 => [2, 3], 2 => [4]], 1, [2, 5], [1 => [1 => 3], 2 => [4]], [1 => 3]],
            [[1 => [2, 3], 3 => [4]], 2, [2, 5], [1 => [2, 3], 3 => [4]], []],
            [[2 => [2, 3]], 2, [], [2 => [2, 3]], [2, 3]],
            [[2 => [2, 3]], 2, [2, 3], [2 => []], []],
        ];
    }

    /**
     * @return array
     */
    public function dataProviderAddCustomerToWebsiteSegments(): array
    {
        return [
            [[], [], [], [1 => []], [1 => []]],
            [[], ['1'], ['1'], [1 => []], [1 => ['1']]],
            [['1','2'], [], ['1','2'], [1 => ['1']], [1 => [0 => '1', 2 => '2']]],
            [['1','2'], ['3'], ['3', '1', '2'], [1 => ['1', '2']], [1 => ['1', '2', '3']]],
        ];
    }
}
