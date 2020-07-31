<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\TargetRule\Test\Unit\Model\Indexer\TargetRule\Action;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;
use Magento\TargetRule\Model\Indexer\TargetRule\Action\Clean;
use Magento\TargetRule\Model\ResourceModel\Index;

class CleanTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Clean
     */
    private $model;

    /** @var StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $storeManagerMock;

    /** @var TimezoneInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $localeDateMock;

    /** @var Index|\PHPUnit_Framework_MockObject_MockObject */
    private $resourceMock;

    public function setUp()
    {
        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->getMock();
        $this->localeDateMock = $this->getMockBuilder(TimezoneInterface::class)
            ->getMock();
        $this->resourceMock = $this->getMockBuilder(Index::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = (new ObjectManager($this))->getObject(
            Clean::class,
            [
                'storeManager' => $this->storeManagerMock,
                'localeDate' => $this->localeDateMock,
            ]
        );
    }

    public function testExecute()
    {
        /** @var Website|\PHPUnit_Framework_MockObject_MockObject $websiteMock */
        $websiteMock = $this->getMockBuilder(Website::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Store|\PHPUnit_Framework_MockObject_MockObject $storeMock */
        $storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \DateTime|\PHPUnit_Framework_MockObject_MockObject $dateMock */
        $dateMock = $this->getMockBuilder(\DateTime::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dateMock->expects($this->once())
            ->method('diff')
            ->willReturn(new \DateInterval('PT0H'));

        $websiteMock->expects($this->once())
            ->method('getDefaultStore')
            ->willReturn($storeMock);
        $websiteMock->expects($this->once())
            ->method('getStoreIds')
            ->willReturn([1, 2]);

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsites')
            ->willReturn([$websiteMock]);

        $this->localeDateMock->expects($this->once())
            ->method('scopeDate')
            ->willReturn($dateMock);

        $this->model->execute();
    }
}
