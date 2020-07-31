<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Test\Unit\Gateway\Http;

use Magento\Eway\Gateway\Helper\Request\Action;
use Magento\Eway\Gateway\Http\UpdateDetailsTransferFactory;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Model\Method\Logger;

/**
 * Class UpdateDetailsTransferFactoryTest
 */
class UpdateDetailsTransferFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var UpdateDetailsTransferFactory
     */
    private $transferFactory;

    /**
     * @var ConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $config;

    /**
     * @var TransferBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $transferBuilder;

    /**
     * @var Action|\PHPUnit_Framework_MockObject_MockObject
     */
    private $action;

    protected function setUp()
    {
        $this->config = $this->getMockBuilder(\Magento\Payment\Gateway\ConfigInterface::class)
            ->getMockForAbstractClass();

        $this->transferBuilder = $this->getMockBuilder(\Magento\Payment\Gateway\Http\TransferBuilder::class)
            ->getMock();

        $this->action = $this->getMockBuilder(\Magento\Eway\Gateway\Helper\Request\Action::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->transferFactory = new UpdateDetailsTransferFactory(
            $this->config,
            $this->transferBuilder,
            $this->action
        );
    }

    /**
     * @param string $prefix
     * @param array $request
     * @param array $config
     * @param string $expectedBuildResult
     *
     * @dataProvider executeDataProvider
     */
    public function testCreate($prefix, $request, $config, $expectedBuildResult)
    {
        $url = 'https://example.com';
        $this->config->expects($this->exactly(4))
            ->method('getValue')
            ->willReturnMap(
                [
                    ['sandbox_flag', null, $config['sandbox_flag']],
                    [$prefix . 'api_key', null, $config['api_key']],
                    [$prefix . 'api_password', null, $config['api_password']],
                ]
            );

        $this->action->expects($this->exactly(1))
            ->method('getUrl')
            ->with('/' . $request['AccessCode'])
            ->willReturn($url);

        $this->transferBuilder->expects($this->once())
            ->method('setMethod')
            ->with('GET')
            ->willReturnSelf();
        $this->transferBuilder->expects($this->once())
            ->method('setAuthUsername')
            ->with($config['api_key'])
            ->willReturnSelf();
        $this->transferBuilder->expects($this->once())
            ->method('setAuthPassword')
            ->with($config['api_password'])
            ->willReturnSelf();
        $this->transferBuilder->expects($this->once())
            ->method('setUri')
            ->with($url)
            ->willReturnSelf();
        $this->transferBuilder
            ->expects($this->once())
            ->method('build')
            ->willReturn($expectedBuildResult);

        $this->assertEquals($expectedBuildResult, $this->transferFactory->create($request));
    }

    /**
     * case 1. Live credentials
     * case 2. Sandbox credentials
     *
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            [
                'live_',
                ['AccessCode' => 'access_code'],
                [
                    'sandbox_flag' => 0,
                    'api_key' => 'live_api_key',
                    'api_password' => 'live_api_password',
                    'gateway' => 'live_gateway'
                ],
                'build'
            ],
            [
                'sandbox_',
                ['AccessCode' => 'access_code'],
                [
                    'sandbox_flag' => 1,
                    'api_key' => 'sandbox_api_key',
                    'api_password' => 'sandbox_api_password',
                    'gateway' => 'sandbox_gateway'
                ],
                'build'
            ]
        ];
    }
}
