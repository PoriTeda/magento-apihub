<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Framework\ForeignKey\Test\Unit\Config;

use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\ForeignKey\Config\Reader;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ReaderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Reader
     */
    protected $reader;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Model\ResourceModel\Type\Db\ConnectionFactory
     */
    protected $connectionFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\App\DeploymentConfig
     */
    protected $deploymentConfig;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\ForeignKey\Config\Processor
     */
    protected $processorMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $dbReaderMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $connectionMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Framework\Config\FileResolverInterface
     */
    protected $fileResolverMock;

    protected function setUp()
    {
        $this->deploymentConfig = $this->createMock(\Magento\Framework\App\DeploymentConfig::class);
        $this->connectionFactoryMock =
            $this->createMock(\Magento\Framework\Model\ResourceModel\Type\Db\ConnectionFactory::class);
        $this->processorMock = $this->createMock(\Magento\Framework\ForeignKey\Config\Processor::class);
        $this->connectionMock = $this->createMock(\Magento\Framework\DB\Adapter\AdapterInterface::class);
        $this->dbReaderMock = $this->createMock(\Magento\Framework\ForeignKey\Config\DbReader::class);
        $this->fileResolverMock = $this->createMock(\Magento\Framework\Config\FileResolverInterface::class);
        $this->reader = new \Magento\Framework\ForeignKey\Config\Reader(
            $this->fileResolverMock,
            $this->createMock(\Magento\Framework\ForeignKey\Config\Converter::class),
            $this->createMock(\Magento\Framework\ForeignKey\Config\SchemaLocator::class),
            $this->createMock(\Magento\Framework\Config\ValidationStateInterface::class),
            $this->connectionFactoryMock,
            $this->deploymentConfig,
            $this->processorMock,
            $this->dbReaderMock
        );
    }

    public function testRead()
    {
        $connectionConfig['default'] = [
            'host' => 'localhost',
            'dbname' => 'example',
            'username' => 'root',
            'password' => '',
            'model' => 'mysql4',
            'initStatements' => 'SET NAMES utf8;',
            'active' => 1,
        ];
        $tables = ['prefix_prefix_table'];
        $databaseTables['prefix_table'] = [
            'name' => 'prefix_table',
            'prefixed_name' => 'prefix_prefix_table',
            'connection' => 'default',
        ];
        $this->deploymentConfig
            ->expects($this->exactly(2))
            ->method('get')
            ->will($this->returnValueMap(
                [
                    [ConfigOptionsListConstants::CONFIG_PATH_DB_PREFIX, null, 'prefix_'],
                    [ConfigOptionsListConstants::CONFIG_PATH_DB_CONNECTIONS, null, $connectionConfig]
                ]
            ));
        $this->connectionFactoryMock
            ->expects($this->once())
            ->method('create')
            ->with($connectionConfig['default'])
            ->willReturn($this->connectionMock);
        $this->connectionMock->expects($this->once())->method('getTables')->willReturn($tables);
        $databaseConstraints = [];
        $this->dbReaderMock->expects($this->once())->method('read')->willReturn($databaseConstraints);
        $this->processorMock->expects($this->once())
            ->method('process')
            ->with([], $databaseConstraints, $databaseTables);
        $this->fileResolverMock->expects($this->once())
            ->method('get')
            ->with('constraints.xml', 'global')
            ->willReturn([]);
        $this->reader->read();
    }
}
