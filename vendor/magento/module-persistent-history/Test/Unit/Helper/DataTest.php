<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PersistentHistory\Test\Unit\Helper;

class DataTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $modulesReaderMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $scopeConfigMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeMock;

    /**
     * @var \Magento\PersistentHistory\Helper\Data
     */
    protected $subject;

    protected function setUp()
    {
        /** @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager  */
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $className = \Magento\PersistentHistory\Helper\Data::class;
        $arguments = $objectManager->getConstructArguments($className);
        /** @var \Magento\Framework\App\Helper\Context $context */
        $context = $arguments['context'];
        $this->storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $this->modulesReaderMock = $arguments['modulesReader'];
        $this->scopeConfigMock = $context->getScopeConfig();
        $this->subject = $objectManager->getObject($className, $arguments);
    }

    public function testGetPersistentConfigFilePath()
    {
        $this->modulesReaderMock->expects($this->once())
            ->method('getModuleDir')
            ->with('etc', 'Magento_PersistentHistory');
        $this->assertEquals('/persistent.xml', $this->subject->getPersistentConfigFilePath());
    }

    public function testIsWishlistPersist()
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('isSetFlag')
            ->with(
                'persistent/options/wishlist',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $this->storeMock
            )
            ->will($this->returnValue(true));
        $this->assertTrue($this->subject->isWishlistPersist($this->storeMock));
    }

    public function testIsOrderedItemsPersist()
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('isSetFlag')
            ->with(
                'persistent/options/recently_ordered',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $this->storeMock
            )
            ->will($this->returnValue(true));
        $this->assertTrue($this->subject->isOrderedItemsPersist($this->storeMock));
    }

    public function testIsCompareProductsPersist()
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('isSetFlag')
            ->with(
                'persistent/options/compare_current',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $this->storeMock
            )
            ->will($this->returnValue(true));
        $this->assertTrue($this->subject->isCompareProductsPersist($this->storeMock));
    }

    public function testIsComparedProductsPersist()
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('isSetFlag')
            ->with(
                'persistent/options/compare_history',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $this->storeMock
            )
            ->will($this->returnValue(true));
        $this->assertTrue($this->subject->isComparedProductsPersist($this->storeMock));
    }

    public function testIsViewedProductsPersist()
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('isSetFlag')
            ->with(
                'persistent/options/recently_viewed',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $this->storeMock
            )
            ->will($this->returnValue(true));
        $this->assertTrue($this->subject->isViewedProductsPersist($this->storeMock));
    }

    public function testIsCustomerAndSegmentsPersist()
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('isSetFlag')
            ->with(
                'persistent/options/customer',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $this->storeMock
            )
            ->will($this->returnValue(true));
        $this->assertTrue($this->subject->isCustomerAndSegmentsPersist($this->storeMock));
    }
}
