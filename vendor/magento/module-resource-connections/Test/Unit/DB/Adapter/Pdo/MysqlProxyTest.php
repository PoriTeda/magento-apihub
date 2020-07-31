<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ResourceConnections\Test\Unit\DB\Adapter\Pdo;

use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\DB\LoggerInterface;
use Magento\ResourceConnections\DB\Adapter\Pdo\MysqlProxy;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MysqlProxyTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var \Magento\Framework\DB\SelectFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $selectFactoryMock;

    /**
     * @var \Magento\Framework\DB\LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    /**
     * @var \Magento\ResourceConnections\DB\Adapter\Pdo\MysqlProxy
     */
    private $mysqlProxy;

    /**
     * @var array
     */
    private $config = [
        'host' => 'testHost',
        'active' => true,
        'initStatements' => 'SET NAMES utf8',
        'type' => 'pdo_mysql',
        'slave' => [
            'host' => 'slaveHost'
        ]
    ];

    /**
     * @var \Magento\Framework\DB\Adapter\Pdo\Mysql|\PHPUnit_Framework_MockObject_MockObject
     */
    private $masterConnectionMock;

    /**
     * @var \Magento\Framework\DB\Adapter\Pdo\Mysql|\PHPUnit_Framework_MockObject_MockObject
     */
    private $slaveConnectionMock;

    /**
     * @var \Magento\Framework\DB\Adapter\Pdo\MysqlFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mysqlFactoryMock;

    protected function setUp()
    {
        $this->objectManager = new ObjectManager($this);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->masterConnectionMock = $this->createMock(\Magento\Framework\DB\Adapter\Pdo\Mysql::class);
        $this->slaveConnectionMock = $this->createMock(\Magento\Framework\DB\Adapter\Pdo\Mysql::class);
        $this->selectFactoryMock = $this->createMock(\Magento\Framework\DB\SelectFactory::class);
        $this->mysqlFactoryMock = $this->createMock(\Magento\Framework\DB\Adapter\Pdo\MysqlFactory::class);
        $this->mysqlProxy = $this->objectManager->getObject(
            MysqlProxy::class,
            [
                'logger' => $this->loggerMock,
                'selectFactory' => $this->selectFactoryMock,
                'config' => $this->config,
                'mysqlFactory' => $this->mysqlFactoryMock
            ]
        );
    }

    /**
     * @param string $methodName
     * @param array $params
     * @dataProvider switchToMasterMethodsDataProvider
     */
    public function testPermanentlySwitchToMaster($methodName, $params)
    {
        $expectedBuilderConfig = $this->config;
        unset($expectedBuilderConfig['slave']);
        $this->mysqlFactoryMock->expects($this->once())
            ->method('create')
            ->with(
                \Magento\Framework\DB\Adapter\Pdo\Mysql::class,
                $expectedBuilderConfig,
                $this->loggerMock
            )
            ->willReturn($this->masterConnectionMock);
        $this->masterConnectionMock->expects($this->once())->method($methodName);
        call_user_func_array([$this->mysqlProxy, $methodName], $params);
        $this->masterConnectionMock->expects($this->once())->method('rawQuery')->with('SOME QUERY');
        $this->mysqlProxy->rawQuery('SOME QUERY');
    }

    public function switchToMasterMethodsDataProvider()
    {
        return [
            ['beginTransaction', []],
            ['commit', []],
            ['rollBack', []],
            ['proccessBindCallback', ['matches']],
            ['setQueryHook', ['hook']],
            ['dropForeignKey', [null, null, null]],
            ['purgeOrphanRecords', [null, null, null, null]],
            ['addColumn', [null, null, null]],
            ['dropColumn', [null, null]],
            ['changeColumn', [null, null, null, null]],
            ['modifyColumn', [null, null, null]],
            ['modifyTables', [null]],
            ['createTableByDdl', [null, null]],
            ['modifyColumnByDdl', [null, null, null]],
            ['changeTableEngine', [null, null]],
            ['changeTableComment', [null, null]],
            ['insertForce', [null, []]],
            ['insertOnDuplicate', [null, []]],
            ['insertMultiple', [null, []]],
            ['insertArray', [null, [], []]],
            ['createTable', [$this->createMock(\Magento\Framework\DB\Ddl\Table::class)]],
            ['createTemporaryTable', [$this->createMock(\Magento\Framework\DB\Ddl\Table::class)]],
            ['createTemporaryTableLike', ['tempTable', 'origin']],
            ['renameTablesBatch', [['table1', 'table2']]],
            ['dropTable', [null]],
            ['dropTemporaryTable', [null]],
            ['truncateTable', [null]],
            ['renameTable', [null, null]],
            ['addIndex', [null, null, null]],
            ['dropIndex', [null, null]],
            ['addForeignKey', [null, null, null, null, null]],
            ['startSetup', []],
            ['endSetup', []],
            ['disableTableKeys', ['table']],
            ['enableTableKeys', ['table']],
            ['insertFromSelect', [$this->createMock(\Magento\Framework\DB\Select::class), 'table']],

            ['updateFromSelect', [$this->createMock(\Magento\Framework\DB\Select::class), 'table']],
            ['deleteFromSelect', [$this->createMock(\Magento\Framework\DB\Select::class), 'table']],
            ['forUpdate', ['SOME QUERY']],
            ['createTrigger', [$this->createMock(\Magento\Framework\DB\Ddl\Trigger::class)]],
            ['dropTrigger', ['triggerName']],
            ['insert', ['table', []]],
            ['update', ['table', [], '']],
            ['delete', ['table', []]],
        ];
    }

    /**
     * @param string $methodName
     * @param array $params
     * @dataProvider passToMasterOnceDataProvider
     */
    public function testPassToMasterOnce($methodName, $params)
    {
        $expectedMasterBuilderConfig = $this->config;
        unset($expectedMasterBuilderConfig['slave']);
        $this->mysqlFactoryMock->expects($this->at(0))
            ->method('create')
            ->with(
                \Magento\Framework\DB\Adapter\Pdo\Mysql::class,
                $expectedMasterBuilderConfig,
                $this->loggerMock
            )
            ->willReturn($this->masterConnectionMock);

        $expectedSlaveBuilderConfig = array_merge($expectedMasterBuilderConfig, $this->config['slave']);

        $this->mysqlFactoryMock->expects($this->at(1))
            ->method('create')
            ->with(
                \Magento\Framework\DB\Adapter\Pdo\Mysql::class,
                $expectedSlaveBuilderConfig,
                $this->loggerMock
            )
            ->willReturn($this->slaveConnectionMock);

        $this->masterConnectionMock->expects($this->once())->method($methodName);
        $this->slaveConnectionMock->expects($this->once())->method('rawQuery')->with('SOME QUERY');
        call_user_func_array([$this->mysqlProxy, $methodName], $params);
        $this->mysqlProxy->rawQuery('SOME QUERY');
    }

    public function passToMasterOnceDataProvider()
    {
        return [
            ['getTransactionLevel', []],
            ['getCreateTable', ['table']],
            ['getForeignKeys', ['table']],
            ['getForeignKeysTree', []],
            ['getIndexList', ['table']],
            ['describeTable', ['table']],
            ['getColumnCreateByDescribe', ['coldata']],
            ['newTable', []],
            ['getColumnDefinitionFromDescribe', ['options']],
        ];
    }

    /**
     * @param string $methodName
     * @param array $params
     * @dataProvider selectConnectionSwitchingDataProvider
     */
    public function testSelectConnectionSwitching($methodName, $params)
    {
        $expectedMasterBuilderConfig = $this->config;
        unset($expectedMasterBuilderConfig['slave']);
        $expectedSlaveBuilderConfig = array_merge($expectedMasterBuilderConfig, $this->config['slave']);

        $this->mysqlFactoryMock->expects($this->at(0))
            ->method('create')
            ->with(
                \Magento\Framework\DB\Adapter\Pdo\Mysql::class,
                $expectedSlaveBuilderConfig,
                $this->loggerMock
            )
            ->willReturn($this->slaveConnectionMock);

        $this->mysqlFactoryMock->expects($this->at(1))
            ->method('create')
            ->with(
                \Magento\Framework\DB\Adapter\Pdo\Mysql::class,
                $expectedMasterBuilderConfig,
                $this->loggerMock
            )
            ->willReturn($this->masterConnectionMock);

        $this->masterConnectionMock->expects($this->once())->method($methodName);
        $this->slaveConnectionMock->expects($this->exactly(2))->method($methodName);
        call_user_func_array([$this->mysqlProxy, $methodName], $params);
        call_user_func_array([$this->mysqlProxy, $methodName], $params);
        $this->mysqlProxy->setUseMasterConnection();
        call_user_func_array([$this->mysqlProxy, $methodName], $params);
    }

    public function selectConnectionSwitchingDataProvider()
    {
        return [
            ['getFetchMode', []],
            ['convertDate', ['2015-01-09']],
            ['convertDateTime', ['2015-01-01 12:22:33']],
            ['rawQuery', ['SOME QUERY']],
            ['rawFetchRow', ['SOME QUERY']],
            ['query', ['SOME QUERY']],
            ['multiQuery', ['MULTIQUERY']],
            ['tableColumnExists', ['table', 'col']],
            ['showTableStatus', ['table']],
            ['quoteInto', ['text', 'val']],
            ['loadDdlCache', ['cacheKey', 'ddltype']],
            ['saveDdlCache', ['cacheKey', 'ddltype', 'data']],
            ['resetDdlCache', []],
            ['disallowDdlCache', []],
            ['allowDdlCache', []],
            ['setCacheAdapter', [$this->createMock(\Magento\Framework\Cache\FrontendInterface::class)]],
            ['isTableExists', ['table']],
            ['formatDate', ['2015-10-12']],
            ['prepareSqlCondition', ['field', 'cond']],
            ['prepareColumnValue', [[1, 2, 3], 'value']],
            ['getCheckSql', ['expr', 'true', 'false']],
            ['getIfNullSql', ['expr', 'value']],
            ['getCaseSql', ['valueName', 'cases', 'default']],
            ['getConcatSql', [[]]],
            ['getLengthSql', ['string']],
            ['getLeastSql', [[]]],
            ['getGreatestSql', [[]]],
            ['getDateAddSql', ['date', 'interval', 'unit']],
            ['getDateSubSql', ['date', 'interval', 'unit']],
            ['getDateFormatSql', ['date', 'format']],
            ['getDatePartSql', ['date']],
            ['getSubstringSql', ['stringExpr', 'pos']],
            ['getStandardDeviationSql', ['expr']],
            ['getDateExtractSql', ['date', 'unit']],
            ['getTableName', ['table']],
            ['getTriggerName', ['trigger', 'time', 'event']],
            ['getIndexName', ['index', 'fields']],
            ['getForeignKeyName', ['primTable', 'priCol', 'refTable', 'refcol']],
            ['selectsByRange', [
                'rangeField',
                $this->createMock(\Magento\Framework\DB\Select::class),
                100
            ]],
            ['getTablesChecksum', ['table1, table2']],
            ['supportStraightJoin', []],
            ['orderRand', [$this->createMock(\Magento\Framework\DB\Select::class), 'field']],
            ['getPrimaryKeyName', ['table']],
            ['decodeVarbinary', ['value']],
            ['getTables', []],
            ['getQuoteIdentifierSymbol', []],
            ['listTables', []],
            ['limit', ['sql', 'count', 'offset']],
            ['isConnected', []],
            ['closeConnection', []],
            ['prepare', ['sql']],
            ['lastInsertId', []],
            ['exec', ['sql']],
            ['setFetchMode', ['mode']],
            ['supportsParameters', ['type']],
            ['getServerVersion', []],
            ['getConnection', []],
            ['getConfig', []],
            ['getProfiler', []],
            ['getStatementClass', []],
            ['setStatementClass', ['class']],
            ['getFetchMode', []],
            ['fetchAll', ['sql']],
            ['fetchRow', ['sql']],
            ['fetchAssoc', ['sql']],
            ['fetchCol', ['sql']],
            ['fetchPairs', ['sql']],
            ['fetchOne', ['sql']],
            ['quote', ['value']],
            ['quoteIdentifier', ['identifier']],
            ['quoteColumnAs', ['table', 'alias']],
            ['quoteTableAs', ['ident']],
            ['lastSequenceId', ['sequenceName']],
            ['nextSequenceId', ['sequenceName']],
        ];
    }

    /**
     * @param array $sqls
     * @param string $sequence
     *
     * @dataProvider selectConnectionSwitchingByQueryDataProvider
     */
    public function testSelectConnectionSwitchingByQuery($sqls, $sequence)
    {
        $expectedMasterBuilderConfig = $this->config;
        unset($expectedMasterBuilderConfig['slave']);
        $expectedSlaveBuilderConfig = array_merge($expectedMasterBuilderConfig, $this->config['slave']);

        if (strpos($sequence, 's') !== false) {
            $this->mysqlFactoryMock->expects($this->at($sequence{0} == 's' ? 0 : 1))
                ->method('create')->with(
                    \Magento\Framework\DB\Adapter\Pdo\Mysql::class,
                    $expectedSlaveBuilderConfig,
                    $this->loggerMock
                )
                ->willReturn($this->slaveConnectionMock);
        }
        if (strpos($sequence, 'm') !== false) {
            $this->mysqlFactoryMock->expects($this->at($sequence{0} == 'm' ? 0 : 1))
                ->method('create')
                ->with(
                    \Magento\Framework\DB\Adapter\Pdo\Mysql::class,
                    $expectedMasterBuilderConfig,
                    $this->loggerMock
                )
                ->willReturn($this->masterConnectionMock);
        }
        $masterCalls = substr_count($sequence, 'm');
        $slaveCalls = substr_count($sequence, 's');
        $this->masterConnectionMock->expects($this->exactly($masterCalls))->method('exec');
        $this->slaveConnectionMock->expects($this->exactly($slaveCalls))->method('exec');

        foreach ($sqls as $query) {
            $this->mysqlProxy->exec($query);
        }
    }

    public function selectConnectionSwitchingByQueryDataProvider()
    {
        return [
            [
                ['SELECT * FROM table WHERE 1'],
                's'
            ],
            [
                [
                    'SELECT * FROM table WHERE 1',
                    'SELECT * FROM xxx',
                    'SELECT * FROM xxx',
                    'SELECT * FROM xxx',
                    'SELECT * FROM xxx',
                    'DROP TABLE `xxx`'
                ],
                'sssssm'
            ],
            [
                ['SELECT * FROM table WHERE 1', 'CREATE TABLE `xxx`', 'SELECT * FROM xxx'],
                'smm'
            ],
            [
                ['CREATE TABLE `xxx`', 'SELECT * FROM xxx', 'SELECT * FROM table WHERE 1'],
                'mmm'
            ],
            [
                [
                    'DELETE FROM xxx',
                    'SELECT * FROM xxx',
                    'SELECT * FROM table WHERE 1'],
                'mmm'
            ],
        ];
    }

    public function testSetProfiler()
    {
        $expectedMasterBuilderConfig = $this->config;
        unset($expectedMasterBuilderConfig['slave']);
        $expectedSlaveBuilderConfig = array_merge($expectedMasterBuilderConfig, $this->config['slave']);

        $this->mysqlFactoryMock->expects($this->at(0))
            ->method('create')
            ->with(
                \Magento\Framework\DB\Adapter\Pdo\Mysql::class,
                $expectedSlaveBuilderConfig,
                $this->loggerMock
            )
            ->willReturn($this->slaveConnectionMock);

        $this->mysqlFactoryMock->expects($this->at(1))
            ->method('create')
            ->with(
                \Magento\Framework\DB\Adapter\Pdo\Mysql::class,
                $expectedMasterBuilderConfig,
                $this->loggerMock
            )
            ->willReturn($this->masterConnectionMock);

        /** @var \Magento\Framework\DB\Profiler $profilerMock */
        $profilerMock = $this->createMock(\Magento\Framework\DB\Profiler::class);

        $this->masterConnectionMock->expects($this->once())->method('setProfiler')->with($profilerMock);
        $this->slaveConnectionMock->expects($this->once())->method('setProfiler')->with($profilerMock);
        $this->mysqlProxy->setProfiler($profilerMock);
    }

    /**
     * @return void
     */
    public function testMethodsList()
    {
        $mysqlClassName = Mysql::class;
        $mysqlClass = new \ReflectionClass($mysqlClassName);
        $mysqlMethodsList = $mysqlClass->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($mysqlMethodsList as $key => $value) {
            $mysqlMethodsList[$key] = $value->name;
        }

        $mysqlProxyClassName = MysqlProxy::class;
        $mysqlProxyClass = new \ReflectionClass($mysqlProxyClassName);

        foreach ($mysqlMethodsList as $mysqlMethod) {
            $this->assertTrue(
                ($mysqlProxyClass->getMethod($mysqlMethod)->getDeclaringClass()->name == $mysqlProxyClassName),
                'MysqlProxy class must have the same public methods as the Mysql. Method - ' . $mysqlMethod . ' missed.'
            );
        }
    }

    /**
     * Tests that predefined list of methods returns proxy instance that is required in chained calls
     *
     * @param string $method
     * @param array $params
     * @dataProvider returnTypeSelfDataProvider
     */
    public function testReturnTypeSelf($method, $params)
    {
        $mysqlProxyClass = new \ReflectionClass(MysqlProxy::class);
        $mysqlProxyClass->getProperty('slaveConnection')->setAccessible(true);
        $mysqlProxyClass->getProperty('masterConnection')->setAccessible(true);
        $proxy = $mysqlProxyClass->newInstance(
            $this->createMock(\Magento\Framework\DB\LoggerInterface::class),
            $this->selectFactoryMock,
            [],
            $this->createMock(\Magento\Framework\DB\Adapter\Pdo\MysqlFactory::class)
        );
        $fieldSetter = function (MysqlProxy $proxy, $propertyName, $propertyValue) {
            $proxy->$propertyName = $propertyValue;
        };
        $masterConnecitonMock = $this->createMock(\Magento\Framework\DB\Adapter\Pdo\Mysql::class);
        $slaveConnecitonMock = $this->createMock(\Magento\Framework\DB\Adapter\Pdo\Mysql::class);
        $fieldSetter = $fieldSetter->bindTo(null, $proxy);
        $fieldSetter($proxy, 'masterConnection', $masterConnecitonMock);
        $fieldSetter($proxy, 'slaveConnection', $slaveConnecitonMock);
        $this->assertInstanceOf(
            MysqlProxy::class,
            call_user_func_array([$proxy, $method], $params),
            'Method ' . $method . ' must return MysqlProxy object reference'
        );
    }

    /**
     * Data provider for testReturnTypeSelf
     *
     * @return array
     */
    public function returnTypeSelfDataProvider()
    {
        return [
            'setUseMasterConnection' => ['setUseMasterConnection', []],
            'beginTransaction' => ['beginTransaction', []],
            'commit' => ['commit', []],
            'rollBack' => ['rollBack', []],
            'dropForeignKey' => ['dropForeignKey', [null, null, null]],
            'purgeOrphanRecords' => ['purgeOrphanRecords', [null, null, null, null]],
            'modifyColumn' => ['modifyColumn', [null, null, null]],
            'modifyTables' => ['modifyTables', [null]],
            'saveDdlCache' => ['saveDdlCache', [null, null, null]],
            'resetDdlCache' => ['resetDdlCache', []],
            'disallowDdlCache' => ['disallowDdlCache', []],
            'allowDdlCache' => ['allowDdlCache', []],
            'modifyColumnByDdl' => ['modifyColumnByDdl', [null, null, null]],
            'setCacheAdapter' => ['setCacheAdapter', [
                $this->createMock(\Magento\Framework\Cache\FrontendInterface::class)
            ]],
            'truncateTable' => ['truncateTable', [null]],
            'startSetup' => ['startSetup', []],
            'endSetup' => ['endSetup', []],
            'disableTableKeys' => ['disableTableKeys', [null]],
            'enableTableKeys' => ['enableTableKeys', [null]],
            'orderRand' => ['orderRand', [
                $this->createMock(\Magento\Framework\DB\Select::class)
            ]],
        ];
    }

    public function testSelectConnection()
    {
        $this->markTestIncomplete('Skipped until direct Mysql connection created.');
        $config = [];
        $config['slave'] = [MysqlProxy::CONFIG_MAX_ALLOWED_LAG => 10];
        /** @var MysqlProxy $proxy */
        $proxy = $this->objectManager->getObject(
            MysqlProxy::class,
            [
                'config' => $config['slave']
            ]
        );
        $proxy->getConnection();
    }
}
