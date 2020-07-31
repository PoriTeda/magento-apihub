<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Test\Unit\Model;

class LoggingTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param int $qty
     * @param int|null $customerSegmentId
     * @param string $expectedText
     * @dataProvider postDispatchCustomerSegmentMatchDataProvider
     */
    public function testPostDispatchCustomerSegmentMatch($qty, $customerSegmentId, $expectedText)
    {
        $requestMock = $this->createMock(\Magento\Framework\App\RequestInterface::class);
        $requestMock->expects(
            $this->atLeastOnce()
        )->method(
            'getParam'
        )->with(
            'id'
        )->will(
            $this->returnValue($customerSegmentId)
        );
        $resourceMock = $this->createMock(\Magento\CustomerSegment\Model\ResourceModel\Segment::class);
        $resourceMock->expects(
            $this->once()
        )->method(
            'getSegmentCustomersQty'
        )->with(
            $customerSegmentId
        )->will(
            $this->returnValue($qty)
        );

        $model = new \Magento\CustomerSegment\Model\Logging($resourceMock, $requestMock);
        $config = new \Magento\Framework\Simplexml\Element('<config/>');
        $eventMock = $this->createPartialMock(\Magento\Logging\Model\Event::class, ['setInfo', '__wakeup']);
        $eventMock->expects($this->once())->method('setInfo')->with($expectedText);

        $model->postDispatchCustomerSegmentMatch($config, $eventMock);
    }

    public function postDispatchCustomerSegmentMatchDataProvider()
    {
        return [
            'specific segment' => [10, 1, "Matched 10 Customers of Segment 1"],
            'no segment' => [10, null, '-']
        ];
    }
}
