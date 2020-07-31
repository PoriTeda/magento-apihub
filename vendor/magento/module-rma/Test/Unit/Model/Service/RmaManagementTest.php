<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Test\Unit\Model\Service;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Class RmaManagementTest
 */
class RmaManagementTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    /**
     * Permission checker
     *
     * @var \Magento\Rma\Model\Rma\PermissionChecker|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $permissionCheckerMock;

    /**
     * Rma repository
     *
     * @var \Magento\Rma\Api\RmaRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $rmaRepositoryMock;

    /**
     * @var \Magento\Rma\Model\Service\RmaManagement
     */
    protected $rmaManagement;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $this->objectManager = new ObjectManager($this);

        $this->permissionCheckerMock = $this->createMock(\Magento\Rma\Model\Rma\PermissionChecker::class);
        $this->rmaRepositoryMock = $this->getMockForAbstractClass(
            \Magento\Rma\Api\RmaRepositoryInterface::class,
            [],
            '',
            false,
            true,
            true,
            []
        );

        $this->rmaManagement = $this->objectManager->getObject(
            \Magento\Rma\Model\Service\RmaManagement::class,
            [
                'permissionChecker' => $this->permissionCheckerMock,
                'rmaRepository' => $this->rmaRepositoryMock
            ]
        );
    }

    /**
     * Run test saveRma method
     *
     * @return void
     */
    public function testSaveRma()
    {
        $rmaMock = $this->getMockForAbstractClass(
            \Magento\Rma\Api\Data\RmaInterface::class,
            [],
            '',
            false,
            true,
            true,
            []
        );

        $this->permissionCheckerMock->expects($this->once())
            ->method('checkRmaForCustomerContext');
        $this->rmaRepositoryMock->expects($this->once())
            ->method('save')
            ->with($rmaMock)
            ->willReturn(true);

        $this->assertTrue($this->rmaManagement->saveRma($rmaMock));
    }

    /**
     * Run test search method
     *
     * @return void
     */
    public function testSearch()
    {
        $expectedResult = 'test-result';

        $searchCriteriaMock = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $searchCriteriaResultMock = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->permissionCheckerMock->expects($this->once())
            ->method('checkRmaForCustomerContext');
        $this->rmaRepositoryMock->expects($this->once())
            ->method('getList')
            ->with($searchCriteriaResultMock)
            ->willReturn($expectedResult);

        $this->assertEquals($expectedResult, $this->rmaManagement->search($searchCriteriaMock));
    }
}
