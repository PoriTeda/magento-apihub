<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Test\Unit\Model\Action;

use Magento\Quote\Model\Quote;
use Magento\Reward\Model\SalesRule\RewardPointCounter;

class SalesruleTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $rewardFactoryMock;

    /**
     * @var \Magento\Reward\Model\Action\Salesrule
     */
    protected $model;

    /**
     * @var RewardPointCounter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $rewardPointCounterMock;

    protected function setUp()
    {
        $this->rewardFactoryMock =
            $this->createMock(\Magento\Reward\Model\ResourceModel\RewardFactory::class);
        $this->rewardPointCounterMock = $this->getMockBuilder(RewardPointCounter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->model = $objectManagerHelper->getObject(
            \Magento\Reward\Model\Action\Salesrule::class,
            [
                'rewardData' => $this->rewardFactoryMock,
                'rewardPointCounter' => $this->rewardPointCounterMock,
            ]
        );
    }

    public function testCanAddRewardPoints()
    {
        $this->assertTrue($this->model->canAddRewardPoints());
    }

    /**
     * @param array $args
     * @param string $expectedResult
     *
     * @dataProvider getHistoryMessageDataProvider
     */
    public function testGetHistoryMessage(array $args, $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->model->getHistoryMessage($args));
    }

    /**
     * @return array
     */
    public function getHistoryMessageDataProvider()
    {
        return [
            [
                'args' => [],
                'expectedResult' => 'Earned promotion extra points from order #',
            ],
            [
                'args' => ['increment_id' => 1],
                'expectedResult' => 'Earned promotion extra points from order #1'
            ]
        ];
    }

    public function testGetPoints()
    {
        $appliedIds = '1,2,1,1,3,4,3';

        /** @var Quote|\PHPUnit_Framework_MockObject_MockObject $quoteMock */
        $quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAppliedRuleIds'])
            ->getMock();
        $quoteMock->expects(self::any())
            ->method('getAppliedRuleIds')
            ->willReturn($appliedIds);

        $this->rewardPointCounterMock->expects(self::any())
            ->method('getPointsForRules')
            ->with(
                [
                    0 => '1',
                    1 => '2',
                    4 => '3',
                    5 => '4',
                ]
            )
            ->willReturn(33);

        $this->model->setQuote($quoteMock);

        $this->assertEquals(33, $this->model->getPoints(1));
    }

    public function testGetPointsWithoutIds()
    {
        /** @var Quote|\PHPUnit_Framework_MockObject_MockObject $quoteMock */
        $quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAppliedRuleIds'])
            ->getMock();
        $quoteMock->expects(self::any())
            ->method('getAppliedRuleIds')
            ->willReturn('');

        $this->model->setQuote($quoteMock);

        $this->assertEquals(0, $this->model->getPoints(1));
    }

    public function testGetPointsWithoutQuote()
    {
        $this->assertEquals(0, $this->model->getPoints(1));
    }
}
