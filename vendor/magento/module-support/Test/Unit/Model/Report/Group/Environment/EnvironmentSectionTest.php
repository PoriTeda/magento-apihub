<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Model\Report\Group\Environment;

use Magento\Support\Model\Report\Group\Environment\EnvironmentSection;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Support\Model\ResourceModel\Report;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EnvironmentSectionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Support\Model\Report\Group\Environment\EnvironmentSection
     */
    protected $environmentReport;

    /**
     * @var \Psr\Log\LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $loggerMock;

    /**
     * @var Report\Environment\PhpInfo|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $phpInfoMock;

    /**
     * @var Report\Environment\OsEnvironment|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $osEnvironmentMock;

    /**
     * @var Report\Environment\ApacheEnvironment|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $apacheEnvironmentMock;

    /**
     * @var Report\Environment\MysqlEnvironment|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mysqlEnvironmentMock;

    /**
     * @var Report\Environment\PhpEnvironment|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $phpEnvironmentMock;

    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @return void
     */
    protected function setUp()
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->loggerMock = $this->createMock(\Psr\Log\LoggerInterface::class);
        $this->phpInfoMock = $this->getMockBuilder(
            \Magento\Support\Model\ResourceModel\Report\Environment\PhpInfo::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->osEnvironmentMock = $this->createMock(
            \Magento\Support\Model\ResourceModel\Report\Environment\OsEnvironment::class
        );
        $this->apacheEnvironmentMock = $this->createMock(
            \Magento\Support\Model\ResourceModel\Report\Environment\ApacheEnvironment::class
        );
        $this->mysqlEnvironmentMock = $this->createMock(
            \Magento\Support\Model\ResourceModel\Report\Environment\MysqlEnvironment::class
        );
        $this->phpEnvironmentMock = $this->createMock(
            \Magento\Support\Model\ResourceModel\Report\Environment\PhpEnvironment::class
        );
        $this->environmentReport = $this->objectManagerHelper->getObject(
            \Magento\Support\Model\Report\Group\Environment\EnvironmentSection::class,
            [
                'logger' => $this->loggerMock,
                'phpInfo' => $this->phpInfoMock,
                'osEnvironment' => $this->osEnvironmentMock,
                'apacheEnvironment' => $this->apacheEnvironmentMock,
                'mysqlEnvironment' => $this->mysqlEnvironmentMock,
                'phpEnvironment' => $this->phpEnvironmentMock
            ]
        );
    }

    /**
     * @return void
     */
    public function testExecuteWithEmptyPhpInfoCollection()
    {
        $expectedResult = [
            'Environment Information' => [
                'headers' => ['Parameter', 'Value'],
                'data' => [],
                'count' => 0
            ]
        ];
        $this->phpInfoMock->expects($this->once())
            ->method('getCollectPhpInfo')
            ->willReturn([]);
        $this->loggerMock->expects($this->once())
            ->method('error');
        $this->assertSame($expectedResult, $this->environmentReport->generate());
    }

    /**
     * @return void
     */
    public function testExecuteMainFlow()
    {
        $osEnvironment = ['OS', 'Linux'];
        $apacheVersion = ['Apache ver', '2.2'];
        $apacheDocRoot = ['Docuent root', '/var/www'];
        $apacheSrvAddress = ['Server address', '192.168.0.1:80'];
        $apacheRemoteAddress = ['Remote address', '10.10.10.10:80'];
        $apacheLoadedModules = ['Loaded Modules', 'mod_rewrite'];
        $mysqlVersion = ['MySQLServer ver', '5.6'];
        $mysqlEngines = ['Supported engines', 'MyISAM; InnoDB'];
        $mysqlDbAmount = ['DB Amount', '2'];
        $mysqlConfiguration = ['DB Conf', 'Some conf info'];
        $mysqlPlugins = ['DB Plugins', 'Plugins info'];
        $phpVersion = ['PHP version', '5.6'];
        $phpLoadedConf = ['Loaded Conf File', 'php.ini'];
        $phpAdditionalIni = ['Additional ini', '(none)'];
        $phpImportantConfSettings = ['Conf settings', 'Settings list'];
        $phpLoadedModules = ['PHP Modules', 'iconv'];
        $expectedResult = [
            'Environment Information' => [
                'headers' => ['Parameter', 'Value'],
                'data' => [
                    $osEnvironment, $apacheVersion, $apacheDocRoot, $apacheSrvAddress,
                    $apacheRemoteAddress, $apacheLoadedModules, $mysqlVersion, $mysqlEngines,
                    $mysqlDbAmount, $mysqlConfiguration, $mysqlPlugins, $phpVersion,
                    $phpLoadedConf, $phpAdditionalIni, $phpImportantConfSettings, $phpLoadedModules
                ],
                'count' => 16
            ]
        ];
        $this->phpInfoMock->expects($this->any())->method('getCollectPhpInfo')
            ->willReturn([]);
        $this->osEnvironmentMock->expects($this->once())
            ->method('getOsInformation')
            ->willReturn($osEnvironment);
        $this->apacheEnvironmentMock->expects($this->once())
            ->method('getVersion')
            ->willReturn($apacheVersion);
        $this->apacheEnvironmentMock->expects($this->once())
            ->method('getDocumentRoot')
            ->willReturn($apacheDocRoot);
        $this->apacheEnvironmentMock->expects($this->once())
            ->method('getServerAddress')
            ->willReturn($apacheSrvAddress);
        $this->apacheEnvironmentMock->expects($this->once())
            ->method('getRemoteAddress')
            ->willReturn($apacheRemoteAddress);
        $this->apacheEnvironmentMock->expects($this->once())
            ->method('getLoadedModules')
            ->willReturn($apacheLoadedModules);
        $this->mysqlEnvironmentMock->expects($this->once())
            ->method('getVersion')
            ->willReturn($mysqlVersion);
        $this->mysqlEnvironmentMock->expects($this->once())
            ->method('getSupportedEngines')
            ->willReturn($mysqlEngines);
        $this->mysqlEnvironmentMock->expects($this->once())
            ->method('getDbAmount')
            ->willReturn($mysqlDbAmount);
        $this->mysqlEnvironmentMock->expects($this->once())
            ->method('getDbConfiguration')
            ->willReturn($mysqlConfiguration);
        $this->mysqlEnvironmentMock->expects($this->once())
            ->method('getPlugins')
            ->willReturn($mysqlPlugins);
        $this->phpEnvironmentMock->expects($this->once())
            ->method('getVersion')
            ->willReturn($phpVersion);
        $this->phpEnvironmentMock->expects($this->once())
            ->method('getLoadedConfFile')
            ->willReturn($phpLoadedConf);
        $this->phpEnvironmentMock->expects($this->once())
            ->method('getAdditionalIniFile')
            ->willReturn($phpAdditionalIni);
        $this->phpEnvironmentMock->expects($this->once())
            ->method('getImportantConfigSettings')
            ->willReturn($phpImportantConfSettings);
        $this->phpEnvironmentMock->expects($this->once())
            ->method('getLoadedModules')
            ->willReturn($phpLoadedModules);
        $this->assertSame($expectedResult, $this->environmentReport->generate());
    }

    /**
     * @return void
     */
    public function testCleanerEmptyArray()
    {
        $osEnvironment = ['OS', 'Linux'];
        $phpImportantConfSettings = ['Conf settings', 'Settings list'];
        $expectedResult = [
            EnvironmentSection::REPORT_TITLE => [
                'headers' => ['Parameter', 'Value'],
                'data' => [$osEnvironment, $phpImportantConfSettings],
                'count' => 2
            ]
        ];
        $this->phpInfoMock->expects($this->any())->method('getCollectPhpInfo')
            ->willReturn([]);
        $this->osEnvironmentMock->expects($this->once())
            ->method('getOsInformation')
            ->willReturn($osEnvironment);
        $this->phpEnvironmentMock->expects($this->once())
            ->method('getImportantConfigSettings')
            ->willReturn($phpImportantConfSettings);
        $this->assertSame($expectedResult, $this->environmentReport->generate());
    }
}
