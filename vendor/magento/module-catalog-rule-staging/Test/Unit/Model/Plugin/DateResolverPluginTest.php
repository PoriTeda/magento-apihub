<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogRuleStaging\Test\Unit\Model\Plugin;

class DateResolverPluginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CatalogRuleStaging\Model\Plugin\DateResolverPlugin
     */
    protected $subject;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $updateRepositoryMock;

    protected function setUp()
    {
        $this->updateRepositoryMock = $this->createMock(\Magento\Staging\Api\UpdateRepositoryInterface::class);
        $this->subject = new \Magento\CatalogRuleStaging\Model\Plugin\DateResolverPlugin($this->updateRepositoryMock);
    }

    public function testBeforeGetFromDate()
    {
        $this->markTestSkipped(
            'Corresponding method has been temporarily disabled'
        );

        $versionId = 100;
        $startTime = 100500;

        $ruleMock = $this->createMock(\Magento\CatalogRule\Model\Rule::class);

        $ruleMock->expects($this->at(0))->method('getData')->with('campaign_id')->willReturn(null);
        $ruleMock->expects($this->at(1))->method('getData')->with('created_in')->willReturn($versionId);
        $ruleMock->expects($this->once())->method('setData')->with('from_date', $startTime)->willReturnSelf();

        $updateMock = $this->createMock(\Magento\Staging\Api\Data\UpdateInterface::class);
        $this->updateRepositoryMock->expects($this->once())
            ->method('get')
            ->with($versionId)
            ->willReturn($updateMock);
        $updateMock->expects($this->once())->method('getStartTime')->willReturn($startTime);

        $this->subject->beforeGetFromDate($ruleMock);
    }

    public function testBeforeGetToDate()
    {
        $this->markTestSkipped(
            'Corresponding method has been temporarily disabled'
        );

        $versionId = 100;
        $startTime = 100500;

        $ruleMock = $this->createMock(\Magento\CatalogRule\Model\Rule::class);
        $ruleMock->expects($this->at(0))->method('getData')->with('campaign_id')->willReturn(null);
        $ruleMock->expects($this->at(1))->method('getData')->with('created_in')->willReturn($versionId);
        $ruleMock->expects($this->once())->method('setData')->with('to_date', $startTime)->willReturnSelf();

        $updateMock = $this->createMock(\Magento\Staging\Api\Data\UpdateInterface::class);
        $this->updateRepositoryMock->expects($this->once())
            ->method('get')
            ->with($versionId)
            ->willReturn($updateMock);
        $updateMock->expects($this->once())->method('getEndTime')->willReturn($startTime);

        $this->subject->beforeGetToDate($ruleMock);
    }
}
