<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdminGws\Test\Unit\Model;

class ConfigTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\AdminGws\Model\Config\Reader|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_readerMock;

    /**
     * @var \Magento\Framework\Config\ScopeInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_configScopeMock;

    /**
     * @var \Magento\Framework\Config\CacheInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_cacheMock;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $serializerMock;

    /**
     * @var \Magento\AdminGws\Model\Config
     */
    protected $_model;

    protected function setUp()
    {
        $cacheId = null;
        $this->_readerMock = $this->createMock(\Magento\AdminGws\Model\Config\Reader::class);
        $this->_configScopeMock = $this->createMock(\Magento\Framework\Config\ScopeInterface::class);
        $this->_cacheMock = $this->createMock(\Magento\Framework\Config\CacheInterface::class);
        $this->serializerMock = $this->createMock(\Magento\Framework\Serialize\SerializerInterface::class);

        $this->_model = new \Magento\AdminGws\Model\Config(
            $this->_readerMock,
            $this->_configScopeMock,
            $this->_cacheMock,
            $cacheId,
            $this->serializerMock
        );
    }

    /**
     * @dataProvider getCallbacksDataProvider
     */
    public function testGetCallbacks($value, $expected)
    {
        $this->_cacheMock->expects($this->any())
            ->method('load')
            ->willReturn('serailizedData');

        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->with('serailizedData')
            ->willReturn($value);

        $this->assertEquals($expected, $this->_model->getCallbacks('group'));
    }

    public function getCallbacksDataProvider()
    {
        return [
            'generic_key_exist' => [['callbacks' => ['group' => 'value']], 'value'],
            'return_default_value' => [['key_one' => 'value'], []]
        ];
    }

    /**
     * @dataProvider getDeniedAclResourcesDataProvider
     */
    public function testGetDeniedAclResources($value, $expected)
    {
        $this->_cacheMock->expects($this->any())
            ->method('load')
            ->willReturn(json_encode($value));

        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->willReturn($value);

        $this->assertEquals($expected, $this->_model->getDeniedAclResources('level'));
    }

    public function getDeniedAclResourcesDataProvider()
    {
        return [
            'generic_key_exist' => [['acl' => ['level' => 'value']], 'value'],
            'return_default_value' => [['key_one' => 'value'], []]
        ];
    }
}
