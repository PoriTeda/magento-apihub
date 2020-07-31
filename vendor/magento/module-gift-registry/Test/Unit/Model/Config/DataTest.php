<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Test\Unit\Model\Config;

class DataTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\GiftRegistry\Model\Config\Data
     */
    protected $_model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_readerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_configScopeMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_cacheMock;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $serializerMock;

    /** @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager */
    private $objectManager;

    protected function setUp()
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->_readerMock = $this->createMock(\Magento\GiftRegistry\Model\Config\Reader::class);
        $this->_configScopeMock = $this->createMock(\Magento\Framework\Config\ScopeInterface::class);
        $this->_cacheMock = $this->getMockBuilder(
            \Magento\Framework\App\Cache\Type\Config::class
        )->disableOriginalConstructor()->getMock();
        $this->serializerMock = $this->createMock(\Magento\Framework\Serialize\SerializerInterface::class);
        $this->_model = $this->objectManager->getObject(
            \Magento\GiftRegistry\Model\Config\Data::class,
            [
                'reader' => $this->_readerMock,
                'configScope' => $this->_configScopeMock,
                'cache' => $this->_cacheMock,
                'serializer' => $this->serializerMock
            ]
        );
    }

    public function testGet()
    {
        $this->_configScopeMock->expects($this->once())->method('getCurrentScope')->will($this->returnValue('global'));
        $this->_cacheMock->expects($this->any())->method('load')->will($this->returnValue(false));
        $this->_readerMock->expects($this->any())->method('read')->will($this->returnValue([]));

        $this->assertEquals([], $this->_model->get());
    }
}
