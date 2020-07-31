<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Test\Unit\Gateway\Http;

use Magento\Eway\Gateway\Helper\Request\Action;
use Magento\Eway\Gateway\Http\TransferFactory;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Model\Method\Logger;

/**
 * Class TransferFactoryTest
 */
class TransferFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TransferFactory
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
        $this->action = $this->getMockBuilder(\Magento\Eway\Gateway\Helper\Request\Action::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->config = $this->getMockBuilder(\Magento\Payment\Gateway\ConfigInterface::class)
            ->getMockForAbstractClass();

        $this->transferBuilder = $this->getMockBuilder(\Magento\Payment\Gateway\Http\TransferBuilder::class)
            ->getMock();

        $this->transferFactory = new TransferFactory(
            $this->config,
            $this->transferBuilder,
            $this->action
        );
    }

    public function testCreate()
    {
        $method = 'POST';
        $headers = ['Content-Type' => 'application/json'];
        $request = ['data1', 'data2'];
        $expectedBuildResult = 'build';
        $url = 'https://example.com';

        $this->action->expects($this->exactly(1))
            ->method('getUrl')
            ->with('')
            ->willReturn($url);

        $this->config->expects($this->exactly(4))
            ->method('getValue')
            ->willReturnMap([
                ['sandbox_flag', null, 1],
                ['sandbox_api_key', null, 'api_key'],
                ['sandbox_api_password', null, 'api_password'],
                ['sandbox_gateway', null, 'https://example.com']
            ]);

        $this->transferBuilder->expects($this->once())
            ->method('setMethod')
            ->with($method)
            ->willReturnSelf();
        $this->transferBuilder->expects($this->once())
            ->method('setHeaders')
            ->with($headers)
            ->willReturnSelf();
        $this->transferBuilder->expects($this->once())
            ->method('setBody')
            ->with(json_encode($request, JSON_UNESCAPED_SLASHES))
            ->willReturnSelf();
        $this->transferBuilder->expects($this->once())
            ->method('setAuthUsername')
            ->with('api_key')
            ->willReturnSelf();
        $this->transferBuilder->expects($this->once())
            ->method('setAuthPassword')
            ->with('api_password')
            ->willReturnSelf();
        $this->transferBuilder->expects($this->once())
            ->method('setUri')
            ->with($url)
            ->willReturnSelf();
        $this->transferBuilder->expects($this->once())
            ->method('build')
            ->willReturn($expectedBuildResult);

        $this->assertEquals($expectedBuildResult, $this->transferFactory->create($request));
    }
}
