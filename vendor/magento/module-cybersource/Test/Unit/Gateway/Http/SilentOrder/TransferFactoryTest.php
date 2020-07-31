<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Test\Unit\Gateway\Http\SilentOrder;

use Magento\Cybersource\Gateway\Http\SilentOrder\TransferFactory;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Http\TransferBuilder;

class TransferFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $clientConfig = [
            'maxredirects' => 5,
            'timeout' => 30,
            'verifypeer' => 1
        ];
        $request = ['data', 'data2'];

        $config = $this->getMockBuilder(ConfigInterface::class)
            ->getMockForAbstractClass();

        $config->method('getValue')
            ->willReturnMap(
                [
                    ['sandbox_flag', null, 1],
                    ['transaction_url_test_mode', null, 'https://test.domain.com']
                ]
            );

        $transferBuilder = new TransferBuilder();

        $factory = new TransferFactory($config, $transferBuilder);
        $buildResult = $factory->create($request);

        self::assertEquals($request, $buildResult->getBody());
        self::assertEquals($clientConfig, $buildResult->getClientConfig());
        self::assertEquals(\Zend_Http_Client::POST, $buildResult->getMethod());
        self::assertEquals('https://test.domain.com', $buildResult->getUri());
        self::assertTrue($buildResult->shouldEncode());
        self::assertEmpty($buildResult->getHeaders());
    }
}
