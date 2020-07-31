<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Model\Backup;

use \Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use \Magento\Support\Model\Backup\Config;
use \Magento\Framework\App\Config\ScopeConfigInterface;

class ConfigTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var \Magento\Support\Model\Backup\Config
     */
    protected $config;

    /**
     * @var \Magento\Framework\App\Helper\Context
     */
    protected $context;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $scopeConfigMock;

    /**
     * @var \Magento\Framework\ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManagerMock;

    /**
     * @return void
     */
    protected function setUp()
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->scopeConfigMock = $this->createMock(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        $this->objectManagerMock = $this->createMock(\Magento\Framework\ObjectManagerInterface::class);

        $this->context = $this->objectManagerHelper->getObject(
            \Magento\Framework\App\Helper\Context::class,
            ['scopeConfig' => $this->scopeConfigMock]
        );
        $this->config = $this->objectManagerHelper->getObject(
            \Magento\Support\Model\Backup\Config::class,
            [
                'context' => $this->context,
                'objectManager' => $this->objectManagerMock
            ]
        );
    }

    /**
     * @return void
     */
    public function testGetBackupItems()
    {
        /** @var \Magento\Support\Model\Backup\Item\Db|\PHPUnit_Framework_MockObject_MockObject $item */
        $itemDb = $this->createPartialMock(\Magento\Support\Model\Backup\Item\Db::class, ['setData']);
        $itemDb->expects($this->once())
            ->method('setData')
            ->with(['test' => 'test']);

        /** @var \Magento\Support\Model\Backup\Item\Code|\PHPUnit_Framework_MockObject_MockObject $item */
        $itemCode = $this->createPartialMock(\Magento\Support\Model\Backup\Item\Code::class, ['setData']);
        $itemCode->expects($this->once())
            ->method('setData')
            ->with(['test2' => 'test2']);

        $configItems = [
            'db' => ['class' => 'Db', 'params' => ['test' => 'test']],
            'code' => ['class' => 'Code', 'params' => ['test2' => 'test2']],
        ];
        $expectedResult = [
            'db' => $itemDb,
            'code' => $itemCode
        ];

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with(Config::XML_BACKUP_ITEMS)
            ->willReturn($configItems);
        $this->objectManagerMock->expects($this->any())
            ->method('create')
            ->willReturnMap([
                ['Code', [], $itemCode],
                ['Db', [], $itemDb],
            ]);

        $this->assertSame($expectedResult, $this->config->getBackupItems());
    }

    /**
     * @param string $type
     * @param string $fileExtension
     * @return void
     * @dataProvider getBackupFileExtensionDataProvider
     */
    public function testGetBackupFileExtension($type, $fileExtension)
    {
        $this->scopeConfigMock->expects($this->any())
            ->method('getValue')
            ->willReturnMap([
                [
                    Config::XML_BACKUP_ITEMS . '/code/params/output_file_extension',
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                    null,
                    'tar.gz'
                ],
                [
                    Config::XML_BACKUP_ITEMS . '/db/params/output_file_extension',
                    ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                    null,
                    'sql.gz'
                ]
            ]);

        $this->assertSame($fileExtension, $this->config->getBackupFileExtension($type));
    }

    /**
     * @return array
     */
    public function getBackupFileExtensionDataProvider()
    {
        return [
            ['type' => 'db', 'fileExtension' => 'sql.gz'],
            ['type' => 'code', 'fileExtension' => 'tar.gz'],
        ];
    }
}
