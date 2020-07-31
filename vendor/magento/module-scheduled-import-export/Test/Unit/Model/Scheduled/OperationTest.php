<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ScheduledImportExport\Test\Unit\Model\Scheduled;

use PHPUnit_Framework_MockObject_MockObject as Mock;
use Magento\ScheduledImportExport\Model\Scheduled\Operation;

/**
 * Class OperationTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class OperationTest extends \PHPUnit\Framework\TestCase
{
    const DATE = '2014/01/01';

    /**
     * Default date value
     *
     * @var string
     */
    protected $_date = '00-00-00';

    /**
     * @var \Magento\ScheduledImportExport\Model\Scheduled\Operation
     */
    protected $model;

    /**
     * @var \Magento\Framework\Model\Context | Mock
     */
    protected $contextMock;

    /**
     * @var \Magento\Framework\Registry | Mock
     */
    protected $registryMock;

    /**
     * @var \Magento\Framework\Filesystem | Mock
     */
    protected $filesystemMock;

    /**
     * @var \Magento\Store\Model\StoreManager | Mock
     */
    protected $storeManagerMock;

    /**
     * @var \Magento\ScheduledImportExport\Model\Scheduled\Operation\GenericFactory | Mock
     */
    protected $genericFactoryMock;

    /**
     * @var \Magento\ScheduledImportExport\Model\Scheduled\Operation\DataFactory | Mock
     */
    protected $dataFactoryMock;

    /**
     * @var \Magento\Framework\App\Config\ValueFactory | Mock
     */
    protected $valueFactoryMock;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime | Mock
     */
    protected $datetimeMock;

    /**
     * @var \Magento\Framework\App\Config | Mock
     */
    protected $configScopeMock;

    /**
     * @var \Magento\Framework\Stdlib\StringUtils | Mock
     */
    protected $stringStdLibMock;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder | Mock
     */
    protected $transportBuilderMock;

    /**
     * @var \Magento\Framework\Filesystem\Io\Ftp | Mock
     */
    protected $ftpMock;

    /**
     * @var \Magento\ScheduledImportExport\Model\ResourceModel\Scheduled\Operation | Mock
     */
    protected $resourceMock;

    /**
     * @var \Magento\Framework\Data\Collection\AbstractDb | Mock
     */
    protected $resourceCollectionMock;

    /**
     * @var \Psr\Log\LoggerInterface | Mock
     */
    protected $loggerInterfaceMock;

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp()
    {
        $this->loggerInterfaceMock = $this->getMockBuilder(\Psr\Log\LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextMock = $this->getMockBuilder(\Magento\Framework\Model\Context::class)
            ->setMethods(['getLogger'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock->expects($this->any())
            ->method('getLogger')
            ->will($this->returnValue($this->loggerInterfaceMock));

        $this->registryMock = $this->getMockBuilder(\Magento\Framework\Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->filesystemMock = $this->getMockBuilder(\Magento\Framework\Filesystem::class)
            ->setMethods(['getDirectoryWrite', 'getDirectoryRead'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManagerMock = $this->getMockBuilder(\Magento\Store\Model\StoreManager::class)
            ->setMethods(['getStore'])
            ->disableOriginalConstructor()
            ->getMock();
        $genericClass = \Magento\ScheduledImportExport\Model\Scheduled\Operation\GenericFactory::class;
        $this->genericFactoryMock = $this->getMockBuilder($genericClass)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $dataClass = \Magento\ScheduledImportExport\Model\Scheduled\Operation\DataFactory::class;
        $this->dataFactoryMock = $this->getMockBuilder($dataClass)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->valueFactoryMock = $this->getMockBuilder(\Magento\Framework\App\Config\ValueFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->datetimeMock = $this->getMockBuilder(\Magento\Framework\Stdlib\DateTime\DateTime::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configScopeMock = $this->getMockBuilder(\Magento\Framework\App\Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->stringStdLibMock = $this->getMockBuilder(\Magento\Framework\Stdlib\StringUtils::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->transportBuilderMock = $this->getMockBuilder(\Magento\Framework\Mail\Template\TransportBuilder::class)
            ->setMethods([
                'setTemplateIdentifier',
                'setTemplateOptions',
                'setTemplateVars',
                'setFrom',
                'addTo',
                'getTransport'
            ])
            ->disableOriginalConstructor()
            ->getMock();
        $this->ftpMock = $this->getMockBuilder(\Magento\Framework\Filesystem\Io\Ftp::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceMock =
            $this->getMockBuilder(\Magento\ScheduledImportExport\Model\ResourceModel\Scheduled\Operation::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceCollectionMock = $this->getMockBuilder(\Magento\Framework\Data\Collection\AbstractDb::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $data = [];
        $serializer = $this->getMockBuilder(\Magento\Framework\Serialize\Serializer\Json::class)
            ->disableOriginalConstructor()
            ->getMock();
        $serializer->expects($this->any())
            ->method('serialize')
            ->willReturnCallback(
                function ($value) {
                    return json_encode($value);
                }
            );
        $serializer->expects($this->any())
            ->method('unserialize')
            ->willReturnCallback(
                function ($value) {
                    return json_decode($value, true);
                }
            );

        $this->model = new \Magento\ScheduledImportExport\Model\Scheduled\Operation(
            $this->contextMock,
            $this->registryMock,
            $this->filesystemMock,
            $this->storeManagerMock,
            $this->genericFactoryMock,
            $this->dataFactoryMock,
            $this->valueFactoryMock,
            $this->datetimeMock,
            $this->configScopeMock,
            $this->stringStdLibMock,
            $this->transportBuilderMock,
            $this->ftpMock,
            $this->resourceMock,
            $this->resourceCollectionMock,
            $data,
            $serializer
        );
    }

    /**
     * @dataProvider getHistoryFilePathDataProvider
     */
    public function testGetHistoryFilePath($fileInfo, $lastRunDate, $expectedPath)
    {
        $model = $this->_getScheduledOperationModel($fileInfo);

        $model->setLastRunDate($lastRunDate);

        $this->assertEquals($expectedPath, $model->getHistoryFilePath());
    }

    /**
     * @return array
     */
    public function getHistoryFilePathDataProvider()
    {
        $dir = Operation::LOG_DIRECTORY . Operation::FILE_HISTORY_DIRECTORY . self::DATE . '/' ;
        return [
            'empty file name' => [
                '$fileInfo' => ['file_format' => 'csv'],
                '$lastRunDate' => null,
                '$expectedPath' => $dir . $this->_date . '_export_catalog_product.csv',
            ],
            'filled file name' => [
                '$fileInfo' => ['file_name' => 'test.xls'],
                '$lastRunDate' => null,
                '$expectedPath' => $dir . $this->_date . '_export_catalog_product.xls',
            ],
            'set last run date' => [
                '$fileInfo' => ['file_name' => 'test.xls'],
                '$lastRunDate' => '11-11-11',
                '$expectedPath' => $dir . '11-11-11_export_catalog_product.xls',
            ]
        ];
    }

    /**
     * Get mocked model
     *
     * @param array $fileInfo
     * @return \Magento\ScheduledImportExport\Model\Scheduled\Operation| \PHPUnit_Framework_MockObject_MockObject
     */
    protected function _getScheduledOperationModel(array $fileInfo)
    {
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $dateModelMock = $this->createPartialMock(\Magento\Framework\Stdlib\DateTime\DateTime::class, ['date']);
        $dateModelMock->expects(
            $this->any()
        )->method(
            'date'
        )->will(
            $this->returnCallback([$this, 'getDateCallback'])
        );

        //TODO Get rid of mocking methods from testing model when this model will be re-factored

        $operationFactory = $this->createPartialMock(
            \Magento\ScheduledImportExport\Model\Scheduled\Operation\DataFactory::class,
            ['create']
        );

        $directory = $this->getMockBuilder(
            \Magento\Framework\Filesystem\Directory\Write::class
        )->disableOriginalConstructor()->getMock();
        $directory->expects($this->once())->method('getAbsolutePath')->will($this->returnArgument(0));
        $filesystem =
            $this->getMockBuilder(\Magento\Framework\Filesystem::class)->disableOriginalConstructor()->getMock();
        $filesystem->expects($this->once())->method('getDirectoryWrite')->will($this->returnValue($directory));

        $params = ['operationFactory' => $operationFactory, 'filesystem' => $filesystem];
        $arguments = $objectManagerHelper->getConstructArguments(
            \Magento\ScheduledImportExport\Model\Scheduled\Operation::class,
            $params
        );
        $arguments['dateModel'] = $dateModelMock;
        $model = $this->getMockBuilder(\Magento\ScheduledImportExport\Model\Scheduled\Operation::class)
            ->setMethods(['getOperationType', 'getEntityType', 'getFileInfo', '_init'])
            ->setConstructorArgs($arguments)
            ->getMock();

        $model->expects($this->once())->method('getOperationType')->will($this->returnValue('export'));
        $model->expects($this->once())->method('getEntityType')->will($this->returnValue('catalog_product'));
        $model->expects($this->once())->method('getFileInfo')->will($this->returnValue($fileInfo));

        return $model;
    }

    /**
     * Callback to use instead of \Magento\Framework\Stdlib\DateTime\DateTime::date()
     *
     * @param string $format
     * @param int|string $input
     * @return string
     */
    public function getDateCallback($format, $input = null)
    {
        if (!empty($format) && $input !== null) {
            return $input;
        }
        if ($format === 'Y/m/d') {
            return self::DATE;
        }
        return $this->_date;
    }

    /**
     * Test saveFileSource() with all valid parameters
     */
    public function testSaveFileSourceFtp()
    {
        $fileContent = 'data to export';
        $fileInfo = [
            'file_name' => 'somefile.csv',
            'file_format' => 'csv',
            'file_path' => '/test',
            'server_type' => \Magento\ScheduledImportExport\Model\Scheduled\Operation\Data::FTP_STORAGE,
        ];
        $datetime = '1970-01-01';
        $operationType = 'export';
        $entityType = 'product';
        $resultFile = '1970-01-01_export_product.csv';
        $scheduledFileName = 'scheduled_filename';
        $serverOptions = $this->getSourceOptions();
        $openArguments = ['path' => $fileInfo['file_path']];
        $writeFilePath = $fileInfo['file_path'] . '/' . $scheduledFileName . '.' . $fileInfo['file_format'];
        $writeResult = true;

        $this->datetimeMock->expects($this->any())
            ->method('date')
            ->will($this->returnValue($datetime));

        $dataMock = $this->getMockBuilder(\Magento\ScheduledImportExport\Model\Scheduled\Operation\Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dataFactoryMock->expects($this->any())
            ->method('create')
            ->will($this->returnValue($dataMock));
        $dataMock->expects($this->any())
            ->method('getServerTypesOptionArray')
            ->will($this->returnValue($serverOptions));

        $exportMock = $this->getMockBuilder(\Magento\ScheduledImportExport\Model\Export::class)
            ->disableOriginalConstructor()
            ->getMock();
        $exportMock->expects($this->at(0))
            ->method('addLogComment');
        $exportMock->expects($this->any())
            ->method('getScheduledFileName')
            ->will($this->returnValue($scheduledFileName));

        $writeDirectoryMock = $this->getMockBuilder(\Magento\Framework\Filesystem\Directory\Write::class)
            ->disableOriginalConstructor()
            ->getMock();
        $writeDirectoryMock->expects($this->any())
            ->method('getAbsolutePath')
            ->will($this->returnValue($resultFile));
        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryWrite')
            ->will($this->returnValue($writeDirectoryMock));

        $this->ftpMock->expects($this->once())
            ->method('open')
            ->with($this->equalTo($openArguments));
        $this->ftpMock->expects($this->once())
            ->method('write')
            ->with($this->equalTo($writeFilePath), $this->equalTo($fileContent))
            ->will($this->returnValue($writeResult));

        $this->setModelData($fileInfo, $operationType, $entityType);

        $result = $this->model->saveFileSource($exportMock, $fileContent);
        $this->assertTrue($result);
    }

    /**
     * Test saveFileSource() through Filesystem library
     */
    public function testSaveFileSourceFile()
    {
        $fileContent = 'data to export';
        $fileInfo = [
            'file_name' => 'somefile.csv',
            'file_format' => 'csv',
            'file_path' => '/test',
            'server_type' => \Magento\ScheduledImportExport\Model\Scheduled\Operation\Data::FILE_STORAGE,
        ];
        $datetime = '1970-01-01';
        $operationType = 'export';
        $entityType = 'product';
        $resultFile = '1970-01-01_export_product.csv';
        $scheduledFileName = 'scheduled_filename';
        $serverOptions = $this->getSourceOptions();

        $this->datetimeMock->expects($this->any())
            ->method('date')
            ->will($this->returnValue($datetime));

        $dataMock = $this->getMockBuilder(\Magento\ScheduledImportExport\Model\Scheduled\Operation\Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dataFactoryMock->expects($this->any())
            ->method('create')
            ->will($this->returnValue($dataMock));
        $dataMock->expects($this->any())
            ->method('getServerTypesOptionArray')
            ->will($this->returnValue($serverOptions));

        $exportMock = $this->getMockBuilder(\Magento\ScheduledImportExport\Model\Export::class)
            ->disableOriginalConstructor()
            ->getMock();
        $exportMock->expects($this->at(0))
            ->method('addLogComment');
        $exportMock->expects($this->any())
            ->method('getScheduledFileName')
            ->will($this->returnValue($scheduledFileName));

        $writeDirectoryMock = $this->getMockBuilder(\Magento\Framework\Filesystem\Directory\Write::class)
            ->disableOriginalConstructor()
            ->getMock();
        $writeDirectoryMock->expects($this->any())
            ->method('getAbsolutePath')
            ->will($this->returnValue($resultFile));
        $writeDirectoryMock->expects($this->any())
            ->method('writeFile')
            ->will($this->returnValue(true));
        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryWrite')
            ->will($this->returnValue($writeDirectoryMock));

        $this->setModelData($fileInfo, $operationType, $entityType);

        $result = $this->model->saveFileSource($exportMock, $fileContent);
        $this->assertTrue($result);
    }

    /**
     * Test saveFileSource() that throws Exception during opening ftp connection
     *
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage We couldn't write file "scheduled_filename.csv" to "/test" with the "ftp" driver.
     */
    public function testSaveFileSourceException()
    {
        $fileContent = 'data to export';
        $fileInfo = [
            'file_name' => 'somefile.csv',
            'file_format' => 'csv',
            'file_path' => '/test',
            'server_type' => \Magento\ScheduledImportExport\Model\Scheduled\Operation\Data::FTP_STORAGE,
        ];
        $datetime = '1970-01-01';
        $operationType = 'export';
        $entityType = 'product';
        $resultFile = '1970-01-01_export_product.csv';
        $scheduledFileName = 'scheduled_filename';
        $serverOptions = $this->getSourceOptions();

        $this->datetimeMock->expects($this->any())
            ->method('date')
            ->will($this->returnValue($datetime));

        $dataMock = $this->getMockBuilder(\Magento\ScheduledImportExport\Model\Scheduled\Operation\Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dataFactoryMock->expects($this->any())
            ->method('create')
            ->will($this->returnValue($dataMock));
        $dataMock->expects($this->any())
            ->method('getServerTypesOptionArray')
            ->will($this->returnValue($serverOptions));

        $exportMock = $this->getMockBuilder(\Magento\ScheduledImportExport\Model\Export::class)
            ->disableOriginalConstructor()
            ->getMock();
        $exportMock->expects($this->at(0))
            ->method('addLogComment');
        $exportMock->expects($this->any())
            ->method('getScheduledFileName')
            ->will($this->returnValue($scheduledFileName));

        $writeDirectoryMock = $this->getMockBuilder(\Magento\Framework\Filesystem\Directory\Write::class)
            ->disableOriginalConstructor()
            ->getMock();
        $writeDirectoryMock->expects($this->any())
            ->method('getAbsolutePath')
            ->will($this->returnValue($resultFile));
        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryWrite')
            ->will($this->returnValue($writeDirectoryMock));

        $this->ftpMock->expects($this->once())
            ->method('open')
            ->will($this->throwException(new \Exception('Can not open file')));

        $this->setModelData($fileInfo, $operationType, $entityType);

        $result = $this->model->saveFileSource($exportMock, $fileContent);
        $this->assertNull($result);
    }

    /**
     * Test getFileSource() if 'file_name' not exists
     *
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedMessage We couldn't read the file source because the file name is empty.
     */
    public function testGetFileSource()
    {
        $fileInfo = [];
        $importMock = $this->getMockBuilder(\Magento\ScheduledImportExport\Model\Import::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model->setFileInfo($fileInfo);
        $result = $this->model->getFileSource($importMock);
        $this->assertNull($result);
    }

    /**
     * Test getFileSource() import data by using ftp
     */
    public function testGetFileSourceFtp()
    {
        $datetime = '1970-01-01';
        $operationType = 'import';
        $entityType = 'product';
        $fileInfo = [
            'file_name' => 'source.csv',
            'file_path' => '/test',
            'server_type' => \Magento\ScheduledImportExport\Model\Scheduled\Operation\Data::FTP_STORAGE,
        ];
        $serverOptions = $this->getSourceOptions();

        $this->datetimeMock->expects($this->any())
            ->method('date')
            ->will($this->returnValue($datetime));

        $dataMock = $this->getMockBuilder(\Magento\ScheduledImportExport\Model\Scheduled\Operation\Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dataFactoryMock->expects($this->any())
            ->method('create')
            ->will($this->returnValue($dataMock));
        $dataMock->expects($this->any())
            ->method('getServerTypesOptionArray')
            ->will($this->returnValue($serverOptions));

        $writeDirectoryMock = $this->getMockBuilder(\Magento\Framework\Filesystem\Directory\Write::class)
            ->disableOriginalConstructor()
            ->getMock();
        $writeDirectoryMock->expects($this->any())
            ->method('getAbsolutePath')
            ->will($this->returnArgument(0));
        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryWrite')
            ->will($this->returnValue($writeDirectoryMock));

        $importMock = $this->getMockBuilder(\Magento\ScheduledImportExport\Model\Import::class)
            ->disableOriginalConstructor()
            ->getMock();
        $importMock->expects($this->any())
            ->method('addLogComment');

        $this->ftpMock->expects($this->any())
            ->method('open');
        $this->ftpMock->expects($this->any())
            ->method('read')
            ->will($this->returnValue(true));

        $this->setModelData($fileInfo, $operationType, $entityType);

        $result = $this->model->getFileSource($importMock);
        $this->assertEquals('csv', pathinfo($result, PATHINFO_EXTENSION));
    }

    /**
     * Test getFileSource() import data by using Filesystem
     */
    public function testGetFileSourceFile()
    {
        $datetime = '1970-01-01';
        $operationType = 'import';
        $entityType = 'product';
        $fileInfo = [
            'file_name' => 'source.csv',
            'file_path' => 'test',
            'server_type' => \Magento\ScheduledImportExport\Model\Scheduled\Operation\Data::FILE_STORAGE,
        ];
        $source = trim($fileInfo['file_path'] . '/' . $fileInfo['file_name'], '\\/');
        $contents = 'test content';

        $serverOptions = $this->getSourceOptions();

        $this->datetimeMock->expects($this->any())
            ->method('date')
            ->will($this->returnValue($datetime));

        $dataMock = $this->getMockBuilder(\Magento\ScheduledImportExport\Model\Scheduled\Operation\Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dataFactoryMock->expects($this->any())
            ->method('create')
            ->will($this->returnValue($dataMock));
        $dataMock->expects($this->any())
            ->method('getServerTypesOptionArray')
            ->will($this->returnValue($serverOptions));

        $writeDirectoryMock = $this->getMockBuilder(\Magento\Framework\Filesystem\Directory\Write::class)
            ->disableOriginalConstructor()
            ->getMock();
        $readDirectoryMock = $this->getMockBuilder(\Magento\Framework\Filesystem\Directory\Read::class)
            ->disableOriginalConstructor()
            ->getMock();
        $readDirectoryMock->expects($this->any())
            ->method('getRelativePath')
            ->will($this->returnArgument(0));
        $readDirectoryMock->expects($this->once())
            ->method('isExist')
            ->with($this->equalTo($source))
            ->will($this->returnValue(true));
        $readDirectoryMock->expects($this->once())
            ->method('readFile')
            ->with($this->equalTo($source))
            ->will($this->returnValue($contents));
        $writeDirectoryMock->expects($this->any())
            ->method('getAbsolutePath')
            ->will($this->returnArgument(0));
        $writeDirectoryMock->expects($this->any())
            ->method('writeFile')
            ->will($this->returnValue(true));
        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryWrite')
            ->will($this->returnValue($writeDirectoryMock));
        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryRead')
            ->will($this->returnValue($readDirectoryMock));

        $importMock = $this->getMockBuilder(\Magento\ScheduledImportExport\Model\Import::class)
            ->disableOriginalConstructor()
            ->getMock();
        $importMock->expects($this->any())
            ->method('addLogComment');

        $this->setModelData($fileInfo, $operationType, $entityType);

        $result = $this->model->getFileSource($importMock);
        $this->assertEquals('csv', pathinfo($result, PATHINFO_EXTENSION));
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @exceptionMessage We couldn't read the import file.
     */
    public function testGetFileSourceFtpException()
    {
        $datetime = '1970-01-01';
        $operationType = 'import';
        $entityType = 'product';
        $fileInfo = [
            'file_name' => 'source.csv',
            'file_path' => '/test',
            'server_type' => \Magento\ScheduledImportExport\Model\Scheduled\Operation\Data::FTP_STORAGE,
        ];
        $serverOptions = $this->getSourceOptions();

        $this->datetimeMock->expects($this->any())
            ->method('date')
            ->will($this->returnValue($datetime));

        $dataMock = $this->getMockBuilder(\Magento\ScheduledImportExport\Model\Scheduled\Operation\Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dataFactoryMock->expects($this->any())
            ->method('create')
            ->will($this->returnValue($dataMock));
        $dataMock->expects($this->any())
            ->method('getServerTypesOptionArray')
            ->will($this->returnValue($serverOptions));

        $writeDirectoryMock = $this->getMockBuilder(\Magento\Framework\Filesystem\Directory\Write::class)
            ->disableOriginalConstructor()
            ->getMock();
        $writeDirectoryMock->expects($this->any())
            ->method('getAbsolutePath')
            ->will($this->returnArgument(0));
        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryWrite')
            ->will($this->returnValue($writeDirectoryMock));

        $importMock = $this->getMockBuilder(\Magento\ScheduledImportExport\Model\Import::class)
            ->disableOriginalConstructor()
            ->getMock();
        $importMock->expects($this->any())
            ->method('addLogComment');

        $this->ftpMock->expects($this->any())
            ->method('open')
            ->willThrowException(new \Magento\Framework\Exception\FileSystemException(__('Can not open file')));

        $this->ftpMock->expects($this->any())
            ->method('read')
            ->will($this->returnValue(true));

        $this->setModelData($fileInfo, $operationType, $entityType);

        $result = $this->model->getFileSource($importMock);
        $this->assertNull($result);
    }

    /**
     * @param array $fileInfo
     * @param string $operationType
     * @param string $entityType
     */
    protected function setModelData(array $fileInfo, $operationType, $entityType)
    {
        $this->model->setFileInfo($fileInfo);
        $this->model->setOperationType($operationType);
        $this->model->setEntityType($entityType);
    }

    /**
     * @return array
     */
    protected function getSourceOptions()
    {
        return [
            \Magento\ScheduledImportExport\Model\Scheduled\Operation\Data::FTP_STORAGE => 'ftp',
            \Magento\ScheduledImportExport\Model\Scheduled\Operation\Data::FILE_STORAGE => 'file',
        ];
    }

    public function testRun()
    {
        $operationType = 'import';
        $entityType = 'product';
        $fileInfo = [
            'file_name' => 'source.csv',
            'file_path' => '/test',
            'server_type' => \Magento\ScheduledImportExport\Model\Scheduled\Operation\Data::FTP_STORAGE,
        ];
        $this->setModelData($fileInfo, $operationType, $entityType);

        $operationInterfaceClass = \Magento\ScheduledImportExport\Model\Scheduled\Operation\OperationInterface::class;
        $operationInterfaceMock = $this->getMockBuilder($operationInterfaceClass)
            ->setMethods([
                'getInstance',
                'setRunDate',
                'runSchedule',
                'initialize',
                'addLogComment',
                'getFormatedLogTrace'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $operationInterfaceMock->expects($this->any())
            ->method('getInstance')
            ->will($this->returnSelf());

        $operationInterfaceMock->expects($this->any())
            ->method('setRunDate')
            ->will($this->returnSelf());

        $operationInterfaceMock->expects($this->once())
            ->method('runSchedule')
            ->will($this->returnValue(false));

        $this->genericFactoryMock->expects($this->any())
            ->method('create')
            ->will($this->returnValue($operationInterfaceMock));

        $writeInterfaceClass = \Magento\Framework\Filesystem\Directory\WriteInterface::class;
        $writeInterfaceMock = $this->getMockBuilder($writeInterfaceClass)
            ->disableOriginalConstructor()
            ->getMock();

        $this->filesystemMock->expects($this->any())
            ->method('getDirectoryWrite')
            ->will($this->returnValue($writeInterfaceMock));

        $storeMock = $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManagerMock->expects($this->any())
            ->method('getStore')
            ->will($this->returnValue($storeMock));

        $this->transportBuilderMock->expects($this->any())
            ->method('setTemplateIdentifier')
            ->will($this->returnSelf());
        $this->transportBuilderMock->expects($this->any())
            ->method('setTemplateOptions')
            ->will($this->returnSelf());
        $this->transportBuilderMock->expects($this->any())
            ->method('setTemplateVars')
            ->will($this->returnSelf());
        $this->transportBuilderMock->expects($this->any())
            ->method('setFrom')
            ->will($this->returnSelf());
        $this->transportBuilderMock->expects($this->any())
            ->method('addTo')
            ->will($this->returnSelf());

        $transportInterfaceMock = $this->getMockBuilder(\Magento\Framework\Mail\TransportInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->transportBuilderMock->expects($this->any())
            ->method('getTransport')
            ->will($this->returnValue($transportInterfaceMock));

        $this->loggerInterfaceMock->expects($this->once())
            ->method('warning')
            ->will($this->returnValue(true));

        $result = $this->model->run();
        $this->assertFalse($result);
    }
}
