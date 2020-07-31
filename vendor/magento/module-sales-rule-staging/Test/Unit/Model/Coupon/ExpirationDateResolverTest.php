<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesRuleStaging\Test\Unit\Model\Coupon;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ExpirationDateResolverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\SalesRule\Api\CouponRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $couponRepositoryMock;

    /**
     * Filter Builder
     *
     * @var \Magento\Framework\Api\FilterBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $filterBuilderMock;

    /**
     * Search Criteria Builder
     *
     * @var \Magento\Framework\Api\SearchCriteriaBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $criteriaBuilderMock;

    /**
     * @var \Magento\SalesRule\Api\RuleRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $ruleRepositoryMock;

    /**
     * @var \Psr\Log\LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    /**
     * @var \Magento\SalesRuleStaging\Model\Coupon\ExpirationDateResolver
     */
    private $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $observerMock;

    protected function setUp()
    {
        $this->couponRepositoryMock = $this->createMock(\Magento\SalesRule\Api\CouponRepositoryInterface::class);
        $methods = ['setField', 'setValue', 'setConditionType', 'create'];
        $this->filterBuilderMock = $this->createPartialMock(\Magento\Framework\Api\FilterBuilder::class, $methods);
        $this->criteriaBuilderMock = $this->createMock(\Magento\Framework\Api\SearchCriteriaBuilder::class);
        $this->ruleRepositoryMock = $this->createMock(\Magento\SalesRule\Api\RuleRepositoryInterface::class);
        $this->loggerMock = $this->createMock(\Psr\Log\LoggerInterface::class);
        $this->observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $this->model = new \Magento\SalesRuleStaging\Model\Coupon\ExpirationDateResolver(
            $this->couponRepositoryMock,
            $this->filterBuilderMock,
            $this->criteriaBuilderMock,
            $this->ruleRepositoryMock,
            $this->loggerMock
        );
    }

    public function testExecute()
    {
        $couponMock = $this->prepareCouponMock();
        $this->couponRepositoryMock->expects($this->once())->method('save')->with($couponMock);
        $this->model->execute($this->observerMock);
    }

    public function testExecuteIfExceptionWasThrown()
    {
        $couponId = 2;
        $exception = new \Exception('MessageText');
        $message = __(
            'An error occurred during processing; coupon with id %1 expiration date'
            . ' wasn\'t updated. The error message was: %2',
            $couponId,
            $exception->getMessage()
        );
        $couponMock = $this->prepareCouponMock();
        $exception = new \Exception('MessageText');
        $this->couponRepositoryMock
            ->expects($this->once())
            ->method('save')
            ->with($couponMock)
            ->willThrowException($exception);
        $couponMock->expects($this->once())->method('getCouponId')->willReturn($couponId);
        $this->loggerMock->expects($this->once())->method('error')->with($message);
        $this->model->execute($this->observerMock);
    }

    /**
     * Prepare the coupon mock for test
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function prepareCouponMock()
    {
        $ruleId = 1;
        $filterMock = $this->createMock(\Magento\Framework\Api\Filter::class);
        $searchCriteria = $this->createMock(\Magento\Framework\Api\SearchCriteria::class);
        $searchResult = $this->createMock(\Magento\SalesRule\Api\Data\CouponSearchResultInterface::class);
        $couponMock = $this->createMock(\Magento\SalesRule\Api\Data\CouponInterface::class);
        $ruleMock = $this->createMock(\Magento\SalesRule\Api\Data\RuleInterface::class);
        $this->observerMock->expects($this->once())->method('getData')->with('entity_ids')->willReturn([$ruleId]);
        $this->filterBuilderMock->expects($this->once())->method('setField')->willReturnSelf();
        $this->filterBuilderMock->expects($this->once())->method('setValue')->with([$ruleId])->willReturnSelf();
        $this->filterBuilderMock->expects($this->once())->method('setConditionType')->willReturnSelf();
        $this->filterBuilderMock->expects($this->once())->method('create')->willReturn($filterMock);
        $this->criteriaBuilderMock->expects($this->once())->method('addFilters')->with([$filterMock]);
        $this->criteriaBuilderMock->expects($this->once())->method('create')->willReturn($searchCriteria);
        $this->couponRepositoryMock
            ->expects($this->once())
            ->method('getList')
            ->with($searchCriteria)
            ->willReturn($searchResult);
        $searchResult->expects($this->once())->method('getItems')->willReturn([$couponMock]);
        $couponMock->expects($this->once())->method('getRuleId')->willReturn($ruleId);
        $this->ruleRepositoryMock->expects($this->once())->method('getById')->with($ruleId)->willReturn($ruleMock);
        $ruleMock->expects($this->once())->method('getToDate')->willReturn('2016-09-20');
        $couponMock->expects($this->once())->method('setExpirationDate')->with('2016-09-20');
        return $couponMock;
    }
}
