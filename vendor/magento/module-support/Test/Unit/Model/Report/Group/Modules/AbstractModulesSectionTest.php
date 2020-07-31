<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Model\Report\Group\Modules;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

abstract class AbstractModulesSectionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var \Magento\Support\Model\Report\Group\Modules\Modules|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $modulesMock;

    /**
     * @var \Magento\Framework\Module\ModuleResource|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceMock;

    /**
     * @return void
     */
    protected function setUp()
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->modulesMock = $this->getMockBuilder(\Magento\Support\Model\Report\Group\Modules\Modules::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceMock = $this->createMock(\Magento\Framework\Module\ModuleResource::class);
    }

    /**
     * @param string $className
     * @param array $dbVersions
     * @param array $enabledModules
     * @param array $allModules
     * @param array $modulesInfo
     * @param array $expectedResult
     * @return void
     * @dataProvider generateDataProvider
     */
    public function testGenerate(
        $className,
        array $dbVersions,
        array $enabledModules,
        array $allModules,
        array $modulesInfo,
        array $expectedResult
    ) {
        /** @var \Magento\Support\Model\Report\Group\Modules\AbstractModuleSection $section */
        $section = $this->objectManagerHelper->getObject(
            $className,
            [
                'modules' => $this->modulesMock,
                'resource' => $this->resourceMock
            ]
        );

        $this->resourceMock->expects($this->any())
            ->method('getDbVersion')
            ->willReturnMap($dbVersions['schemaVersions']);
        $this->resourceMock->expects($this->any())
            ->method('getDataVersion')
            ->willReturnMap($dbVersions['dataVersions']);

        $this->modulesMock->expects($this->once())
            ->method('getFullModulesList')
            ->willReturn($allModules);
        $this->modulesMock->expects($this->any())
            ->method('getModulePath')
            ->willReturnMap($modulesInfo['modulePathMap']);
        $this->modulesMock->expects($this->any())
            ->method('isCustomModule')
            ->willReturnMap($modulesInfo['customModuleMap']);
        $this->modulesMock->expects($this->any())
            ->method('getOutputFlagInfo')
            ->willReturnMap($modulesInfo['outputFlagInfoMap']);
        $this->modulesMock->expects($this->any())
            ->method('isModuleEnabled')
            ->willReturnMap($enabledModules);

        $this->assertSame($expectedResult, $section->generate());
    }

    /**
     * @return array
     */
    abstract public function generateDataProvider();
}
