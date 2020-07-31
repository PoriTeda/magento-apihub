<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Test\Unit\Model\SalesRule;

use Magento\Reward\Model\SalesRule\RewardPointCounter;
use Magento\SalesRule\Api\Data\RuleExtensionInterface;
use Magento\SalesRule\Api\Data\RuleInterface;
use Magento\SalesRule\Api\RuleRepositoryInterface;

class RewardPointCounterTest extends \PHPUnit\Framework\TestCase
{
    /** @var RewardPointCounter */
    private $model;

    /** @var RuleRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $ruleRepositoryMock;

    protected function setUp()
    {
        $this->ruleRepositoryMock = $this->getMockBuilder(RuleRepositoryInterface::class)
            ->getMock();

        $this->model = new RewardPointCounter(
            $this->ruleRepositoryMock
        );
    }

    public function testGetPointsForRules()
    {
        $ruleIds = [1, 2, 3, 4];

        /** @var RuleExtensionInterface|\PHPUnit_Framework_MockObject_MockObject $attributesOneMock */
        $attributesOneMock = $this->getMockBuilder(RuleExtensionInterface::class)
            ->setMethods(['getRewardPointsDelta'])
            ->getMockForAbstractClass();
        $attributesOneMock->expects(self::any())
            ->method('getRewardPointsDelta')
            ->willReturn(12);

        /** @var RuleExtensionInterface|\PHPUnit_Framework_MockObject_MockObject $attributesTwoMock */
        $attributesTwoMock = $this->getMockBuilder(RuleExtensionInterface::class)
            ->setMethods(['getRewardPointsDelta'])
            ->getMockForAbstractClass();
        $attributesTwoMock->expects(self::any())
            ->method('getRewardPointsDelta')
            ->willReturn(21);

        /** @var RuleExtensionInterface|\PHPUnit_Framework_MockObject_MockObject $attributesThreeMock */
        $attributesThreeMock = $this->getMockBuilder(RuleExtensionInterface::class)
            ->setMethods(['getRewardPointsDelta'])
            ->getMockForAbstractClass();
        $attributesThreeMock->expects(self::any())
            ->method('getRewardPointsDelta')
            ->willReturn(null);

        /** @var RuleInterface|\PHPUnit_Framework_MockObject_MockObject $ruleOneMock */
        $ruleOneMock = $this->getMockBuilder(RuleInterface::class)
            ->getMock();
        $ruleOneMock->expects(self::any())
            ->method('getExtensionAttributes')
            ->willReturn($attributesOneMock);

        /** @var RuleInterface|\PHPUnit_Framework_MockObject_MockObject $ruleTwoMock */
        $ruleTwoMock = $this->getMockBuilder(RuleInterface::class)
            ->getMock();
        $ruleTwoMock->expects(self::any())
            ->method('getExtensionAttributes')
            ->willReturn($attributesTwoMock);

        /** @var RuleInterface|\PHPUnit_Framework_MockObject_MockObject $ruleThreeMock */
        $ruleThreeMock = $this->getMockBuilder(RuleInterface::class)
            ->getMock();
        $ruleThreeMock->expects(self::any())
            ->method('getExtensionAttributes')
            ->willReturn($attributesThreeMock);

        /** @var RuleInterface|\PHPUnit_Framework_MockObject_MockObject $ruleFourMock */
        $ruleFourMock = $this->getMockBuilder(RuleInterface::class)
            ->getMock();
        $ruleFourMock->expects(self::any())
            ->method('getExtensionAttributes')
            ->willReturn(null);

        $this->ruleRepositoryMock->expects(self::any())
            ->method('getById')
            ->willReturnMap([
                [1, $ruleOneMock],
                [2, $ruleTwoMock],
                [3, $ruleThreeMock],
                [4, $ruleFourMock],
            ]);

        $this->assertEquals(33, $this->model->getPointsForRules($ruleIds));
    }

    public function testGetPointsForRulesWithoutIds()
    {
        $this->assertEquals(0, $this->model->getPointsForRules([]));
    }
}
