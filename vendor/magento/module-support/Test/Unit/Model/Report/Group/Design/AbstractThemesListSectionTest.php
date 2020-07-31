<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Model\Report\Group\Design;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

abstract class AbstractThemesListSectionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Theme\Model\ResourceModel\Theme\CollectionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $themeCollectionFactoryMock;

    /**
     * @var \Magento\Theme\Model\ResourceModel\Theme\Collection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $themeCollectionMock;

    /**
     * @var \Magento\Theme\Model\Theme|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $themeMock;

    /**
     * @var \Magento\Theme\Model\Theme|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $parentThemeMock;

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

        $this->themeCollectionMock = $this->getMockBuilder(\Magento\Theme\Model\ResourceModel\Theme\Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'addFieldToFilter', 'setOrder', 'getItems', 'getIterator'])
            ->getMock();
        $this->themeCollectionFactoryMock = $this->getMockBuilder(
            \Magento\Theme\Model\ResourceModel\Theme\CollectionFactory::class
        )->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->themeCollectionFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->themeCollectionMock);
        $this->themeCollectionMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->themeCollectionMock->expects($this->once())->method('setOrder')->willReturnSelf();
        $this->themeCollectionMock->expects($this->once())->method('load')->willReturnSelf();

        $this->parentThemeMock = $this->getMockBuilder(\Magento\Theme\Model\Theme::class)
            ->setMethods(['getThemePath'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Create theme model mock
     *
     * @param string $themePath
     * @param \Magento\Theme\Model\Theme|\PHPUnit_Framework_MockObject_MockObject|null $parentThemeMock
     * @param string|null $parentThemePath
     * @return \Magento\Theme\Model\Theme|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getThemeMock($themePath, $parentThemeMock = null, $parentThemePath = null)
    {
        $themeMock = $this->createPartialMock(\Magento\Theme\Model\Theme::class, ['getParentTheme', 'getThemePath']);
        $themeMock->expects($this->atLeastOnce())->method('getParentTheme')->willReturn($parentThemeMock);
        $this->parentThemeMock->expects($this->any())->method('getThemePath')->willReturn($parentThemePath);
        $themeMock->expects($this->once())->method('getThemePath')->willReturn($themePath);

        return $themeMock;
    }
}
