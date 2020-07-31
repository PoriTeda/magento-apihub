<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\BannerCustomerSegment\Test\Unit\Observer;

use Magento\BannerCustomerSegment\Observer\AddCustomerSegmentFilterToSelect;

class AddCustomerSegmentFilterToSelectTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Magento\BannerCustomerSegment\Observer\AddCustomerSegmentFilterToSelect
     */
    private $addCustomerSegmentFilterToSelectObserver;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $_bannerSegmentLink;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $_segmentCustomer;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $_segmentHelper;

    /**
     * @var \Magento\Framework\DB\Select
     */
    private $_select;

    protected function setUp()
    {
        $this->_bannerSegmentLink = $this->createPartialMock(
            \Magento\BannerCustomerSegment\Model\ResourceModel\BannerSegmentLink::class,
            ['loadBannerSegments', 'saveBannerSegments', 'addBannerSegmentFilter', '__wakeup']
        );
        $this->_segmentCustomer = $this->createPartialMock(
            \Magento\CustomerSegment\Model\Customer::class,
            ['getCurrentCustomerSegmentIds', '__wakeup']
        );
        $this->_segmentHelper = $this->createPartialMock(
            \Magento\CustomerSegment\Helper\Data::class,
            ['isEnabled', 'addSegmentFieldsToForm']
        );

        $this->addCustomerSegmentFilterToSelectObserver = new AddCustomerSegmentFilterToSelect(
            $this->_segmentHelper,
            $this->_bannerSegmentLink,
            $this->_segmentCustomer
        );

        $this->_select = new \Magento\Framework\DB\Select(
            $this->getMockForAbstractClass(\Magento\Framework\DB\Adapter\Pdo\Mysql::class, [], '', false),
            $this->getMockForAbstractClass(\Magento\Framework\DB\Select\SelectRenderer::class, [], '', false)
        );
    }

    protected function tearDown()
    {
        $this->_segmentHelper = null;
        $this->_bannerSegmentLink = null;
        $this->_segmentCustomer = null;
        $this->addCustomerSegmentFilterToSelectObserver = null;
    }

    public function addCustomerSegmentFilterDataProvider()
    {
        return ['segments' => [[123, 456]], 'no segments' => [[]]];
    }

    protected function _setFixtureSegmentIds(array $segmentIds)
    {
        $this->_segmentHelper->expects($this->any())->method('isEnabled')->will($this->returnValue(true));

        $this->_segmentCustomer->expects(
            $this->once()
        )->method(
            'getCurrentCustomerSegmentIds'
        )->will(
            $this->returnValue($segmentIds)
        );
    }

    /**
     * @dataProvider addCustomerSegmentFilterDataProvider
     * @param array $segmentIds
     */
    public function testAddCustomerSegmentFilterToSelect(array $segmentIds)
    {
        $this->_setFixtureSegmentIds($segmentIds);

        $this->_bannerSegmentLink->expects(
            $this->once()
        )->method(
            'addBannerSegmentFilter'
        )->with(
            $this->_select,
            $segmentIds
        );

        $this->addCustomerSegmentFilterToSelectObserver->execute(
            new \Magento\Framework\Event\Observer(
                ['event' => new \Magento\Framework\DataObject(['select' => $this->_select])]
            )
        );
    }

    public function testAddCustomerSegmentFilterToSelectDisabled()
    {
        $this->_segmentHelper->expects($this->any())->method('isEnabled')->will($this->returnValue(false));

        $this->_segmentCustomer->expects($this->never())->method('getCurrentCustomerSegmentIds');
        $this->_bannerSegmentLink->expects($this->never())->method('addBannerSegmentFilter');

        $this->addCustomerSegmentFilterToSelectObserver->execute(
            new \Magento\Framework\Event\Observer(
                ['event' => new \Magento\Framework\DataObject(['select' => $this->_select])]
            )
        );
    }
}
