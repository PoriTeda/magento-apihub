<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\ForeignKey\Test\Unit\ObjectRelationProcessor;

class EnvironmentConfigTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\ForeignKey\ObjectRelationProcessor\EnvironmentConfig
     */
    private $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $configMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $cacheMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $jsonDecoderMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $jsonEncoderMock;

    /**
     * @var string
     */
    private $cacheId;

    /**
     * @var array
     */
    private $connectionNames;

    protected function setUp()
    {
        $this->configMock = $this->createMock(\Magento\Framework\App\ResourceConnection\ConfigInterface::class);
        $this->cacheMock = $this->createMock(\Magento\Framework\Config\CacheInterface::class);
        $this->connectionNames = ['connectionName1', 'connectionName2'];
        $this->cacheId = 'connection_config_cache';

        $this->jsonDecoderMock = $this->createMock(\Magento\Framework\Json\DecoderInterface::class);
        $this->jsonEncoderMock = $this->createMock(\Magento\Framework\Json\EncoderInterface::class);

        $this->model = new \Magento\Framework\ForeignKey\ObjectRelationProcessor\EnvironmentConfig(
            $this->configMock,
            $this->cacheMock,
            $this->jsonDecoderMock,
            $this->jsonEncoderMock,
            $this->connectionNames
        );
    }

    public function testIsScalableEnvironmentIfConnectionNamesIsEmpty()
    {
        $this->model = new \Magento\Framework\ForeignKey\ObjectRelationProcessor\EnvironmentConfig(
            $this->configMock,
            $this->cacheMock,
            $this->jsonDecoderMock,
            $this->jsonEncoderMock,
            []
        );
        $this->assertFalse($this->model->isScalable());
    }

    public function testIsScalableEnvironment()
    {
        $this->cacheMock->expects($this->once())->method('load')->with($this->cacheId)->willReturn(false);
        $this->configMock->expects($this->at(0))
            ->method('getConnectionName')
            ->with($this->connectionNames[0])
            ->willReturn($this->connectionNames[0]);

        $this->configMock->expects($this->at(1))
            ->method('getConnectionName')
            ->with($this->connectionNames[1])
            ->willReturn(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);

        $connectionConfig = [
            $this->connectionNames[0] => false,
            $this->connectionNames[1] => true
        ];
        $this->jsonEncoderMock->expects($this->once())->method('encode')->willReturn(json_encode($connectionConfig));

        $this->cacheMock->expects($this->once())
            ->method('save')
            ->with(
                json_encode($connectionConfig),
                $this->cacheId,
                [\Magento\Framework\App\Cache\Type\Config::CACHE_TAG]
            )
            ->willReturn(true);

        $this->assertTrue($this->model->isScalable());
    }

    public function testIsScalableEnvironmentWhenConnectionConfigCached()
    {
        $connectionConfig = [
            $this->connectionNames[0] => true,
            $this->connectionNames[1] => true
        ];

        $this->jsonDecoderMock->expects($this->once())->method('decode')->willReturn($connectionConfig);

        $this->cacheMock->expects($this->once())
            ->method('load')
            ->with($this->cacheId)
            ->willReturn(json_encode($connectionConfig));

        $this->assertFalse($this->model->isScalable());
    }
}
