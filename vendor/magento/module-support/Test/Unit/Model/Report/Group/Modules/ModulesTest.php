<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Model\Report\Group\Modules;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ModulesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\Module\FullModuleList|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fullModuleListMock;

    /**
     * @var \Magento\Framework\Module\ModuleList|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $enabledModuleListMock;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configMock;

    /**
     * @var \Magento\Store\Model\StoreManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManagerMock;

    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var \Magento\Framework\Module\Dir|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $moduleDirMock;

    /**
     * @var \Magento\Support\Model\Report\Group\Modules\Modules
     */
    protected $modules;

    /**
     * @return void
     */
    protected function setUp()
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->fullModuleListMock = $this->createMock(\Magento\Framework\Module\FullModuleList::class);
        $this->enabledModuleListMock = $this->createMock(\Magento\Framework\Module\ModuleList::class);
        $this->configMock = $this->createMock(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        $this->storeManagerMock = $this->createMock(\Magento\Store\Model\StoreManager::class);
        $this->moduleDirMock = $this->createMock(\Magento\Framework\Module\Dir::class);

        $this->modules = $this->objectManagerHelper->getObject(
            \Magento\Support\Model\Report\Group\Modules\Modules::class,
            [
                'fullModuleList'=>  $this->fullModuleListMock,
                'storeManager' =>   $this->storeManagerMock,
                'moduleList' =>     $this->enabledModuleListMock,
                'moduleDir' =>      $this->moduleDirMock,
                'config' =>         $this->configMock
            ]
        );
    }

    /**
     * @param string $moduleName
     * @param bool $expectedResult
     * @return void
     * @dataProvider isModuleEnabledDataProvider
     */
    public function testIsModuleEnabled($moduleName, $expectedResult)
    {
        $modulesName = ['Magento_Backend', 'Magento_Cms'];

        $this->enabledModuleListMock->expects($this->once())
            ->method('getNames')
            ->willReturn($modulesName);

        $this->assertSame($expectedResult, $this->modules->isModuleEnabled($moduleName));
    }

    /**
     * @return array
     */
    public function isModuleEnabledDataProvider()
    {
        return [
            ['moduleName' => 'Magento_Backend', 'expectedResult' => true],
            ['moduleName' => 'Magento_Cms', 'expectedResult' => true],
            ['moduleName' => 'Magento_Catalog', 'expectedResult' => false]
        ];
    }

    /**
     * @return void
     */
    public function testGetFullModulesList()
    {
        $modulesList = [
            'Magento_Backend' => ['setup_version' => '2.0.0'],
            'Magento_Cms' => ['setup_version' => '2.0.0']
        ];
        $expectedResult = [
            'Magento_Backend' => '2.0.0',
            'Magento_Cms' => '2.0.0'
        ];

        $this->fullModuleListMock->expects($this->once())
            ->method('getAll')
            ->willReturn($modulesList);

        $this->assertSame($expectedResult, $this->modules->getFullModulesList());
    }

    /**
     * @param string $moduleName
     * @param string $modulePath
     * @return void
     * @dataProvider getModulePathDataProvider
     */
    public function testGetModulePath($moduleName, $modulePath)
    {
        $this->moduleDirMock->expects($this->once())
            ->method('getDir')
            ->with($moduleName, '')
            ->willReturn($modulePath);

        $this->assertSame($modulePath, $this->modules->getModulePath($moduleName));
    }

    /**
     * @return array
     */
    public function getModulePathDataProvider()
    {
        return [
            [
                'moduleName' => 'Magento_Backend',
                'modulePath' => 'app/code/Magento/Backend/'
            ],
            [
                'moduleName' => 'Vendor_HelloWorld',
                'modulePath' => 'app/code/Vendor/HelloWorld/'
            ]
        ];
    }

    /**
     * @param string $moduleName
     * @param bool $customFlag
     * @return void
     * @dataProvider isCustomModuleDataProvider
     */
    public function testIsCustomModule($moduleName, $customFlag)
    {
        $this->assertSame($customFlag, $this->modules->isCustomModule($moduleName));
    }

    /**
     * @return array
     */
    public function isCustomModuleDataProvider()
    {
        return [
            ['moduleName' => 'Magento_Backend', 'customFlag' => false],
            ['moduleName' => 'Magento_Cms', 'customFlag' => false],
            ['moduleName' => 'Vendor_HelloWorld', 'customFlag' => true],
        ];
    }

    /**
     * @param string $moduleName
     * @param array $websites
     * @param array $isSetFlagReturnValues
     * @param array $expectedResult
     * @return void
     * @dataProvider getOutputFlagInfoDataProvider
     */
    public function testGetOutputFlagInfo(
        $moduleName,
        array $websites,
        array $isSetFlagReturnValues,
        array $expectedResult
    ) {
        $this->configMock->expects($this->any())
            ->method('isSetFlag')
            ->willReturnMap($isSetFlagReturnValues);

        $this->storeManagerMock->expects($this->once())
            ->method('getWebsites')
            ->willReturn($websites);

        $this->assertSame($expectedResult, $this->modules->getOutputFlagInfo($moduleName));
    }

    /**
     * @param string $name
     * @param int $id
     * @param \Magento\Store\Model\Store[]|\PHPUnit_Framework_MockObject_MockObject[] $stores
     * @return \Magento\Store\Model\Website|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getWebsiteMock($name, $id, array $stores = [])
    {
        $websiteMock = $this->getMockBuilder(\Magento\Store\Model\Website::class)
            ->setMethods(['getName', 'getId', 'getStores'])
            ->disableOriginalConstructor()
            ->getMock();
        $websiteMock->expects($this->any())
            ->method('getName')
            ->willReturn($name);
        $websiteMock->expects($this->any())
            ->method('getId')
            ->willReturn($id);
        $websiteMock->expects($this->any())
            ->method('getStores')
            ->willReturn($stores);

        return $websiteMock;
    }

    /**
     * @param string $name
     * @param int $id
     * @return \Magento\Store\Model\Store|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getStoreMock($name, $id)
    {
        $storeMock = $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->setMethods(['getName', 'getId'])
            ->disableOriginalConstructor()
            ->getMock();
        $storeMock->expects($this->any())
            ->method('getName')
            ->willReturn($name);
        $storeMock->expects($this->any())
            ->method('getId')
            ->willReturn($id);

        return $storeMock;
    }

    /**
     * @return array
     */
    public function getOutputFlagInfoDataProvider()
    {
        $path = 'advanced/modules_disable_output/';
        return [
            [
                'moduleName' => 'Magento_Backend',
                'websites' => [],
                'isSetFlagReturnValues' => [
                    [$path . 'Magento_Backend', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null, true],
                ],
                'expectedResult' => [
                    '{[Default Config] = Disable}'
                ]
            ],
            [
                'moduleName' => 'Magento_Backend',
                'websites' => [],
                'isSetFlagReturnValues' => [
                    [$path . 'Magento_Backend', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null, false],
                ],
                'expectedResult' => [
                    '{[Default Config] = Enable}'
                ]
            ],
            [
                'moduleName' => 'Magento_Backend',
                'websites' => [
                    $this->getWebsiteMock('Default Website', 1),
                    $this->getWebsiteMock('Second Website', 2)
                ],
                'isSetFlagReturnValues' => [
                    [$path . 'Magento_Backend', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null, false],
                    [$path . 'Magento_Backend', ScopeInterface::SCOPE_WEBSITES, 1, false],
                    [$path . 'Magento_Backend', ScopeInterface::SCOPE_WEBSITES, 2, true],
                ],
                'expectedResult' => [
                    '{[Default Config] = Enable}',
                    '{[Default Website] = Enable}',
                    '{[Second Website] = Disable}'
                ]
            ],
            [
                'moduleName' => 'Magento_Backend',
                'websites' => [
                    $this->getWebsiteMock(
                        'Default Website',
                        1,
                        [
                            $this->getStoreMock('Default Store', 1),
                            $this->getStoreMock('Second Store', 2)
                        ]
                    ),
                ],
                'isSetFlagReturnValues' => [
                    [$path . 'Magento_Backend', ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null, false],
                    [$path . 'Magento_Backend', ScopeInterface::SCOPE_WEBSITES, 1, true],
                    [$path . 'Magento_Backend', ScopeInterface::SCOPE_STORES, 1, true],
                    [$path . 'Magento_Backend', ScopeInterface::SCOPE_STORES, 2, false],
                ],
                'expectedResult' => [
                    '{[Default Config] = Enable}',
                    '{[Default Website] = Disable}',
                    '{[Default Website] => [Default Store] = Disable}',
                    '{[Default Website] => [Second Store] = Enable}'
                ]
            ],
        ];
    }
}
