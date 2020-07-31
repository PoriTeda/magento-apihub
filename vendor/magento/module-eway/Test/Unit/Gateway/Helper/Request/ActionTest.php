<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Test\Unit\Gateway\Helper\Request;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Eway\Gateway\Helper\Request\Action;

/**
 * Class ActionTest
 *
 * @see \Magento\Eway\Gateway\Helper\Request\Action
 */
class ActionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configMock;

    /**
     * Set up
     */
    protected function setUp()
    {
        $this->configMock = $this->getMockForAbstractClass(\Magento\Payment\Gateway\ConfigInterface::class);
    }

    /**
     * Run test for getUrl method
     *
     * @param string $action
     * @param string $expected
     * @param string $additionalPath
     * @param array $sandboxFlag
     * @param array $gateway
     *
     * @dataProvider dataProviderGetUrl
     */
    public function testGetUrl($action, $expected, $additionalPath, array $sandboxFlag, array $gateway)
    {
        $action = new Action($action, $this->configMock);

        $this->configMock->expects($this->at(0))
            ->method('getValue')
            ->with($sandboxFlag['key'])
            ->willReturn($sandboxFlag['value']);
        $this->configMock->expects($this->at(1))
            ->method('getValue')
            ->with($gateway['key'])
            ->willReturn($gateway['value']);

        $this->assertEquals($expected, $action->getUrl($additionalPath));
    }

    /**
     * @return array
     */
    public function dataProviderGetUrl()
    {
        return [
            [
                'action' => 'test',
                'expected' => 'live-gateway/test',
                'additionalPath' => '',
                'sandboxFlag' => ['key' => 'sandbox_flag', 'value' => 0],
                'gateway' => ['key' => 'live_gateway', 'value' => 'live-gateway']
            ],
            [
                'action' => 'test',
                'expected' => 'live-gateway/test/additional-path',
                'additionalPath' => '/additional-path',
                'sandboxFlag' => ['key' => 'sandbox_flag', 'value' => 0],
                'gateway' => ['key' => 'live_gateway', 'value' => 'live-gateway']
            ],
            [
                'action' => 'test',
                'expected' => 'sandbox-gateway/test',
                'additionalPath' => '',
                'sandboxFlag' => ['key' => 'sandbox_flag', 'value' => 1],
                'gateway' => ['key' => 'sandbox_gateway', 'value' => 'sandbox-gateway']
            ],
            [
                'action' => 'test',
                'expected' => 'sandbox-gateway/test/additional-path',
                'additionalPath' => '/additional-path',
                'sandboxFlag' => ['key' => 'sandbox_flag', 'value' => 1],
                'gateway' => ['key' => 'sandbox_gateway', 'value' => 'sandbox-gateway']
            ]
        ];
    }
}
