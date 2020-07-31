<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Worldpay\Test\Unit\Model\Ui;

use Magento\Framework\Url;
use Magento\Worldpay\Model\Ui\ConfigProvider;

/**
 * Class ConfigProviderTest
 *
 * Test for class \Magento\Worldpay\Model\Ui\ConfigProvider
 */
class ConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Url|\PHPUnit_Framework_MockObject_MockObject
     */
    private $urlBuilderMock;

    protected function setUp()
    {
        $this->urlBuilderMock = $this->getMockBuilder(\Magento\Framework\Url::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUrl'])
            ->getMock();
    }

    /**
     * Run test getConfig method
     */
    public function testGetConfig()
    {
        $configProvider = new ConfigProvider($this->urlBuilderMock);
        $this->urlBuilderMock->expects(static::exactly(2))
            ->method('getUrl')
            ->willReturn(ConfigProvider::TRANSACTION_DATA_URL);

        $this->assertEquals(
            [
                'payment' => [
                    ConfigProvider::WORLDPAY_CODE => [
                        'transactionDataUrl' => $this->urlBuilderMock->getUrl(ConfigProvider::TRANSACTION_DATA_URL)
                    ]
                ]
            ],
            $configProvider->getConfig()
        );
    }
}
