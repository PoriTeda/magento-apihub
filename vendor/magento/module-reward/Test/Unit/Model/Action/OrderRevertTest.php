<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Test\Unit\Model\Action;

class OrderRevertTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Reward\Model\Action\OrderRevert
     */
    protected $model;

    protected function setUp()
    {
        $this->model = new \Magento\Reward\Model\Action\OrderRevert();
    }

    /**
     * @param array $args
     * @param string $expectedResult
     *
     * @dataProvider getHistoryMessageDataProvider
     * @covers \Magento\Reward\Model\Action\OrderRevert::getHistoryMessage
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
                'expectedResult' => 'Reverted from incomplete order #',
            ],
            [
                'args' => ['increment_id' => 1],
                'expectedResult' => 'Reverted from incomplete order #1'
            ]
        ];
    }
}
