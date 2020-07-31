<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\BannerCustomerSegment\Test\Unit\Observer;

use Magento\BannerCustomerSegment\Observer\SaveCustomerSegmentRelations;

class SaveCustomerSegmentRelationsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Magento\BannerCustomerSegment\Observer\SaveCustomerSegmentRelations
     */
    private $saveCustomerSegmentRelationsObserver;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $_bannerSegmentLink;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $_segmentHelper;

    protected function setUp()
    {
        $this->_bannerSegmentLink = $this->createPartialMock(
            \Magento\BannerCustomerSegment\Model\ResourceModel\BannerSegmentLink::class,
            ['loadBannerSegments', 'saveBannerSegments', 'addBannerSegmentFilter', '__wakeup']
        );

        $this->_segmentHelper = $this->createPartialMock(
            \Magento\CustomerSegment\Helper\Data::class,
            ['isEnabled', 'addSegmentFieldsToForm']
        );

        $this->saveCustomerSegmentRelationsObserver = new SaveCustomerSegmentRelations(
            $this->_segmentHelper,
            $this->_bannerSegmentLink
        );
    }

    protected function tearDown()
    {
        $this->_bannerSegmentLink = null;
        $this->_segmentHelper = null;
        $this->saveCustomerSegmentRelationsObserver = null;
    }

    public function testSaveCustomerSegmentRelations()
    {
        $this->_segmentHelper->expects($this->any())->method('isEnabled')->will($this->returnValue(true));

        $segmentIds = [123, 456];
        $banner = new \Magento\Framework\DataObject(['id' => 42, 'customer_segment_ids' => $segmentIds]);

        $this->_bannerSegmentLink->expects(
            $this->once()
        )->method(
            'saveBannerSegments'
        )->with(
            $banner->getId(),
            $segmentIds
        );

        $this->saveCustomerSegmentRelationsObserver->execute(
            new \Magento\Framework\Event\Observer(
                [
                    'event' => new \Magento\Framework\DataObject(['banner' => $banner]),
                ]
            )
        );
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Customer segments associated with a dynamic block are expected to be defined as an array
     */
    // @codingStandardsIgnoreEnd
    public function testSaveCustomerSegmentRelationsException()
    {
        $this->_segmentHelper->expects($this->any())->method('isEnabled')->will($this->returnValue(true));

        $banner = new \Magento\Framework\DataObject(['id' => 42, 'customer_segment_ids' => 'invalid']);

        $this->_bannerSegmentLink->expects($this->never())->method('saveBannerSegments');

        $this->saveCustomerSegmentRelationsObserver->execute(
            new \Magento\Framework\Event\Observer(
                [
                    'event' => new \Magento\Framework\DataObject(['banner' => $banner]),
                ]
            )
        );
    }

    public function testSaveCustomerSegmentRelationsDisabled()
    {
        $this->_segmentHelper->expects($this->any())->method('isEnabled')->will($this->returnValue(false));

        $banner = new \Magento\Framework\DataObject(['id' => 42, 'customer_segment_ids' => [123, 456]]);

        $this->_bannerSegmentLink->expects($this->never())->method('saveBannerSegments');

        $this->saveCustomerSegmentRelationsObserver->execute(
            new \Magento\Framework\Event\Observer(
                [
                    'event' => new \Magento\Framework\DataObject(['banner' => $banner]),
                ]
            )
        );
    }
}
