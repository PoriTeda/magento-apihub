<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Test\Unit\Model\Plugin;

use Magento\Reward\Model\Plugin\TotalsCollector;

class TotalsCollectorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Reward\Model\Plugin\TotalsCollector
     */
    private $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $quoteMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $totalsCollectorMock;

    protected function setUp()
    {
        $this->totalsCollectorMock = $this->createMock(\Magento\Quote\Model\Quote\TotalsCollector::class);
        $this->quoteMock = $this->createPartialMock(
            \Magento\Quote\Model\Quote::class,
            [
                'setRewardPointsBalance',
                'setRewardCurrencyAmount',
                'setBaseRewardCurrencyAmount',
            ]
        );
        $this->model = new TotalsCollector();
    }

    public function testBeforeCollectResetsRewardAmount()
    {
        $this->quoteMock->expects($this->once())->method('setRewardPointsBalance')->with(0);
        $this->quoteMock->expects($this->once())->method('setRewardCurrencyAmount')->with(0);
        $this->quoteMock->expects($this->once())->method('setBaseRewardCurrencyAmount')->with(0);

        $this->model->beforeCollect($this->totalsCollectorMock, $this->quoteMock);
    }
}
