<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ResourceConnections\Test\Unit\DB\ConnectionAdapter;

use Magento\Framework\DB\LoggerInterface;
use Magento\ResourceConnections\DB\Adapter\Pdo\MysqlProxy;
use Magento\Framework\App\Request\Http as RequestHttp;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class MysqlTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var \Magento\Framework\App\Request\Http|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestMock;

    /**
     * @var \Magento\Framework\DB\LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    /**
     * @var \Magento\Framework\DB\SelectFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $selectFactoryMock;

    /**
     * @var \Magento\Framework\DB\Adapter\Pdo\MysqlFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mysqlFactoryMock;

    protected function setUp()
    {
        $this->objectManager = new ObjectManager($this);
        $this->requestMock = $this->createPartialMock(RequestHttp::class, ['isSafeMethod']);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->selectFactoryMock = $this->createMock(\Magento\Framework\DB\SelectFactory::class);
        $this->mysqlFactoryMock = $this->createMock(\Magento\Framework\DB\Adapter\Pdo\MysqlFactory::class);
    }

    /**
     * Test that real adapter is returned for non-safe method
     */
    public function testInstantiationForNonSafeMethodWithoutSlave()
    {
        $config = [
            'host' => 'testHost',
            'active' => true,
            'initStatements' => 'SET NAMES utf8',
            'type' => 'pdo_mysql'
        ];
        $this->requestMock->expects($this->never())
            ->method('isSafeMethod')
            ->willReturn(false);
        $this->assertCreateAdapter(
            \Magento\Framework\DB\Adapter\Pdo\Mysql::class,
            $config,
            $config
        );
    }

    /**
     * Test that real adapter is returned for non-safe method even if slave is set
     */
    public function testInstantiationForSafeMethodWithSlave()
    {
        $config = [
            'host' => 'testHost',
            'active' => true,
            'initStatements' => 'SET NAMES utf8',
            'type' => 'pdo_mysql',
            'slave' => [
                'host' => 'slaveHost'
            ]
        ];
        $expectedBuildConfig = $config;
        unset($expectedBuildConfig['slave']);
        $this->requestMock->expects($this->once())
            ->method('isSafeMethod')
            ->willReturn(false);
        $this->assertCreateAdapter(
            \Magento\Framework\DB\Adapter\Pdo\Mysql::class,
            $config,
            $expectedBuildConfig
        );
    }

    /**
     * Test that real adapter is returned for safe method if slave is not set
     */
    public function testInstantiationForSafeRequestWithoutSlave()
    {
        $config = [
            'host' => 'testHost',
            'active' => true,
            'initStatements' => 'SET NAMES utf8',
            'type' => 'pdo_mysql',
        ];
        $this->requestMock->expects($this->never())
            ->method('isSafeMethod');
        $this->assertCreateAdapter(
            \Magento\Framework\DB\Adapter\Pdo\Mysql::class,
            $config,
            $config
        );
    }

    /**
     * Test that adapter proxy is returned for safe method if slave config is set
     */
    public function testInstantiationForSafeRequestWithSlave()
    {
        $config = [
            'host' => 'testHost',
            'active' => true,
            'initStatements' => 'SET NAMES utf8',
            'type' => 'pdo_mysql',
            'slave' => [
                'host' => 'slaveHost'
            ]
        ];
        $this->requestMock->expects($this->once())
            ->method('isSafeMethod')
            ->willReturn(true);
        $this->assertCreateAdapter(
            MysqlProxy::class,
            $config,
            $config
        );
    }

    /**
     * Create Mysql adapter, assert that factory used with correct arguments
     *
     * @param string $expectedClassName
     * @param array $config
     * @param array $expectedConfig
     * @return void
     */
    private function assertCreateAdapter(
        $expectedClassName,
        array $config,
        array $expectedConfig
    ) {
        $this->mysqlFactoryMock->expects($this->once())
            ->method('create')
            ->with(
                $expectedClassName,
                $expectedConfig,
                $this->loggerMock
            );
        $mysqlAdapter = $this->objectManager->getObject(
            \Magento\ResourceConnections\DB\ConnectionAdapter\Mysql::class,
            [
                'config' => $config,
                'request' => $this->requestMock,
                'mysqlFactory' => $this->mysqlFactoryMock
            ]
        );
        $mysqlAdapter->getConnection($this->loggerMock, $this->selectFactoryMock);
    }
}
