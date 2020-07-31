<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Test\Unit\Gateway\Http\Soap;

use Magento\Cybersource\Gateway\Http\Soap\TransferFactory;

class TransferFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $clientConfig = [
            'wsdl' => 'https://test.domain.com'
        ];
        $request = ['data', 'data2'];
        $method = 'runTransaction';
        $uri = '';
        $build = 'buildResult';

        $config = $this->getMockBuilder(\Magento\Payment\Gateway\ConfigInterface::class)
            ->getMockForAbstractClass();

        $config->expects($this->exactly(4))
            ->method('getValue')
            ->willReturnMap(
                [
                    ['sandbox_flag', null, 1],
                    ['wsdl_test_mode', null, 'https://test.domain.com'],
                    ['merchant_id', null, 'merchant_id_value'],
                    ['transaction_key', null, 'transaction_key_value']
                ]
            );

        $transferBuilder = $this->getMockBuilder(\Magento\Payment\Gateway\Http\TransferBuilder::class)
            ->getMock();
        $transferBuilder->expects($this->once())
            ->method('setClientConfig')
            ->with($clientConfig)
            ->willReturnSelf();
        $transferBuilder->expects($this->once())
            ->method('setHeaders')
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
            ->willReturn($build);

        $factory = new TransferFactory($config, $transferBuilder);
        $this->assertEquals($build, $factory->create($request));
    }
}
