<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Worldpay\Test\Unit\Gateway\Http;

use Magento\Worldpay\Gateway\Http\TransferFactory;

class TransferFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $clientConfig = ['timeout' => 60, 'verifypeer' => 1];
        $request = ['request'];
        $method = \Zend_Http_Client::POST;
        $uri = 'https://secure-test.worldpay.com/wcc/iadmin';
        $expectedBuildResult = 'buildResult';

        $config = $this->getMockBuilder(\Magento\Payment\Gateway\ConfigInterface::class)
            ->getMockForAbstractClass();

        $config->expects($this->exactly(2))
            ->method('getValue')
            ->willReturnMap(
                [
                    ['sandbox_flag', null, 1],
                    ['iadmin_url_test', null, 'https://secure-test.worldpay.com/wcc/iadmin'],
                ]
            );

        $transferBuilder = $this->getMockBuilder(\Magento\Payment\Gateway\Http\TransferBuilder::class)
            ->getMock();
        $transferBuilder->expects($this->once())
            ->method('setClientConfig')
            ->with($clientConfig)
            ->willReturnSelf();
        $transferBuilder->expects($this->once())
            ->method('setBody')
            ->with($request)
            ->willReturnSelf();
        $transferBuilder->expects($this->once())
            ->method('setMethod')
            ->with($method)
            ->willReturnSelf();
        $transferBuilder->expects($this->once())
            ->method('setUri')
            ->with($uri)
            ->willReturnSelf();
        $transferBuilder->expects($this->once())
            ->method('build')
            ->willReturn($expectedBuildResult);

        $factory = new TransferFactory($config, $transferBuilder);
        $this->assertEquals($expectedBuildResult, $factory->create($request));
    }
}
