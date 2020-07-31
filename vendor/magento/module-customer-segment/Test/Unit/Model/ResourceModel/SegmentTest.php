<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Test\Unit\Model\ResourceModel;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SegmentTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CustomerSegment\Model\ResourceModel\Segment
     */
    protected $_resourceModel;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_resource;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $connectionMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_configShare;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_conditions;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_segment;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $queryResolverMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $dateTimeMock;

    protected function setUp()
    {
        $this->connectionMock = $this->getMockForAbstractClass(
            \Magento\Framework\DB\Adapter\AdapterInterface::class,
            [],
            '',
            false,
            true,
            true,
            ['query', 'insertMultiple', 'beginTransaction', 'commit']
        );

        $this->_resource = $this->createMock(\Magento\Framework\App\ResourceConnection::class);
        $this->_resource->expects($this->any())->method('getTableName')->will($this->returnArgument(0));
        $this->_resource->expects(
            $this->any()
        )->method(
            'getConnection'
        )->willReturn($this->connectionMock);

        $this->_configShare = $this->createPartialMock(
            \Magento\Customer\Model\Config\Share::class,
            ['isGlobalScope', 'isWebsiteScope', '__wakeup']
        );
        $this->_segment = $this->createPartialMock(
            \Magento\CustomerSegment\Model\Segment::class,
            ['getConditions', 'getWebsiteIds', 'getId', '__wakeup']
        );

        $this->_conditions = $this->createPartialMock(
            \Magento\CustomerSegment\Model\Segment\Condition\Combine\Root::class,
            ['getConditions', 'getSatisfiedIds']
        );

        $contextMock = $this->createMock(\Magento\Framework\Model\ResourceModel\Db\Context::class);
        $contextMock->expects($this->once())->method('getResources')->willReturn($this->_resource);
        $this->queryResolverMock = $this->createMock(\Magento\Quote\Model\QueryResolver::class);
        $this->dateTimeMock = $this->createMock(\Magento\Framework\Stdlib\DateTime::class);
        $this->_resourceModel = new \Magento\CustomerSegment\Model\ResourceModel\Segment(
            $contextMock,
            $this->createMock(\Magento\CustomerSegment\Model\ResourceModel\Helper::class),
            $this->_configShare,
            $this->dateTimeMock,
            $this->createMock(\Magento\Quote\Model\ResourceModel\Quote::class),
            $this->queryResolverMock
        );
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function testSaveCustomersFromSelect()
    {
        $select =
            $this->createPartialMock(\Magento\Framework\DB\Select::class, ['joinLeft', 'from', 'columns']);
        $this->_segment->expects($this->any())->method('getId')->will($this->returnValue(3));
        $statement = $this->createPartialMock(
            \Zend_Db_Statement::class,
            ['closeCursor', 'columnCount', 'errorCode', 'errorInfo', 'fetch', 'nextRowset', 'rowCount']
        );
        $websites = [8, 9];
        $statement->expects(
            $this->at(0)
        )->method(
            'fetch'
        )->will(
            $this->returnValue(['entity_id' => 4, 'website_id' => $websites[0]])
        );
        $statement->expects(
            $this->at(1)
        )->method(
            'fetch'
        )->will(
            $this->returnValue(['entity_id' => 5, 'website_id' => $websites[1]])
        );
        $statement->expects($this->at(2))->method('fetch')->will($this->returnValue(false));
        $this->connectionMock->expects(
            $this->any()
        )->method(
            'query'
        )->with(
            $this->equalTo($select)
        )->will(
            $this->returnValue($statement)
        );
        $callback = function ($data) use ($websites) {
            foreach ($data as $item) {
                if (!isset($item['website_id']) || !in_array($item['website_id'], $websites)) {
                    return false;
                }
            }
            return true;
        };

        $this->connectionMock->expects(
            $this->once()
        )->method(
            'insertMultiple'
        )->with(
            $this->equalTo('magento_customersegment_customer'),
            $this->callback($callback)
        );
        $this->connectionMock->expects($this->once())->method('beginTransaction');
        $this->connectionMock->expects($this->once())->method('commit');

        $this->_resourceModel->saveCustomersFromSelect($this->_segment, $select);
    }

    /**
     * @dataProvider aggregateMatchedCustomersDataProvider
     * @param bool $scope
     * @param array $websites
     * @param mixed $websiteIds
     */
    public function testAggregateMatchedCustomers($scope, $websites, $websiteIds)
    {
        $nowDate = '2015-04-23 02:04:51';
        $this->dateTimeMock->expects($this->any())
            ->method('formatDate')
            ->withAnyParameters()
            ->willReturn($nowDate);

        $customerIds = [1];
        if ($scope) {
            $this->_conditions->expects($this->once())
                ->method('getSatisfiedIds')
                ->with($this->equalTo(null))
                ->willReturn($customerIds);
        } else {
            $this->_conditions->expects($this->exactly(2))
                ->method('getSatisfiedIds')
                ->withConsecutive([current($websites)], [end($websites)])
                ->willReturn($customerIds);
        }

        $this->_segment->expects($scope ? $this->once() : $this->exactly(2))
            ->method('getConditions')
            ->willReturn($this->_conditions);
        $this->_segment->expects($this->once())
            ->method('getWebsiteIds')
            ->willReturn($websiteIds);
        $this->_segment->expects($this->any())
            ->method('getId')
            ->willReturn(3);
        $insertData = [
            [
                'segment_id' => 3,
                'customer_id' => 1,
                'website_id' => reset($websites),
                'added_date' => $nowDate,
                'updated_date' => $nowDate,
            ],
        ];
        if (!$scope) {
            $insertData[] = [
                'segment_id' => 3,
                'customer_id' => 1,
                'website_id' => end($websites),
                'added_date' => $nowDate,
                'updated_date' => $nowDate,
            ];
        }
        $this->connectionMock->expects(
            $this->once()
        )->method(
            'insertMultiple'
        )->with(
            $this->equalTo('magento_customersegment_customer'),
            $insertData
        );
        $this->connectionMock->expects($this->exactly(2))->method('beginTransaction');
        $this->connectionMock->expects($this->exactly(2))->method('commit');

        $this->_configShare->expects($this->any())->method('isGlobalScope')->willReturn($scope);
        $this->_configShare->expects($this->any())->method('isWebsiteScope')->willReturn(!$scope);
        $this->_resourceModel->aggregateMatchedCustomers($this->_segment);
    }

    public function aggregateMatchedCustomersDataProvider()
    {
        return [[true, [7], [7]], [false, [7, 9], [7, 9]]];
    }
}
