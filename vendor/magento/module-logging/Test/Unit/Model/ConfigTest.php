<?php
/**
 * Test \Magento\Logging\Model\Config
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Logging\Test\Unit\Model;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class ConfigTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Logging\Model\Config\Data
     */
    private $storageMock;

    /**
     * @var \Magento\Logging\Model\Config
     */
    private $config;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeConfigMock;

    /**
     * @var Json|\PHPUnit_Framework_MockObject_MockObject
     */
    private $serializerMock;

    protected function setUp()
    {
        $objectManager = new ObjectManager($this);
        $this->storageMock = $this->getMockBuilder(\Magento\Logging\Model\Config\Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $loggingConfig = [
            'actions' => [
                'test_action_withlabel' => ['label' => 'Test Action Label'],
                'test_action_withoutlabel' => [],
            ],
            'test' => ['label' => 'Test Label'],
            'configured_log_group' => [
                'label' => 'Log Group With Configuration',
                'actions' => [
                    'adminhtml_checkout_index' => [
                        'log_name' => 'configured_log_group',
                        'action' => 'view',
                        'expected_models' => [\Magento\Framework\Model\AbstractModel::class => []],
                    ],
                ],
            ],
        ];
        $this->storageMock->expects(
            $this->once()
        )->method(
            'get'
        )->with(
            $this->equalTo('logging')
        )->will(
            $this->returnValue($loggingConfig)
        );
        $this->scopeConfigMock = $this->getMockBuilder(\Magento\Framework\App\Config\ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->serializerMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->config = $objectManager->getObject(
            \Magento\Logging\Model\Config::class,
            [
                'dataStorage' => $this->storageMock,
                'scopeConfig' => $this->scopeConfigMock,
                'serializer' => $this->serializerMock,
            ]
        );
    }

    public function testLabels()
    {
        $expected = ['test' => 'Test Label', 'configured_log_group' => 'Log Group With Configuration'];
        $result = $this->config->getLabels();
        $this->assertEquals($expected, $result);
    }

    public function testGetActionLabel()
    {
        $expected = 'Test Action Label';
        $result = $this->config->getActionLabel('test_action_withlabel');
        $this->assertEquals($expected, $result);
    }

    public function testGetActionWithoutLabel()
    {
        $this->assertEquals('test_action_withoutlabel', $this->config->getActionLabel('test_action_withoutlabel'));
        $this->assertEquals('nonconfigured_action', $this->config->getActionLabel('nonconfigured_action'));
    }

    public function testGetSystemConfigValues()
    {
        $config = ['enterprise_checkout' => 1, 'customer' => 1];
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('admin/magento_logging/actions', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
            ->willReturn('serializedConfig');
        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->with('serializedConfig')
            ->willReturn($config);
        $this->assertEquals($config, $this->config->getSystemConfigValues());
    }

    public function testGetSystemConfigValuesNegative()
    {
        $expected = ['test' => 1, 'configured_log_group' => 1];
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('admin/magento_logging/actions', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
            ->willReturn(null);
        $this->serializerMock->expects($this->never())
            ->method('unserialize');
        $this->assertEquals($expected, $this->config->getSystemConfigValues());
    }

    public function testHasSystemConfigValues()
    {
        $config = ['enterprise_checkout' => 1, 'customer' => 1];

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('admin/magento_logging/actions', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
            ->willReturn('serializedConfig');
        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->with('serializedConfig')
            ->willReturn($config);
        $this->assertTrue($this->config->hasSystemConfigValue('enterprise_checkout'));
        $this->assertFalse($this->config->hasSystemConfigValue('enterprise_catalogevent'));
    }

    public function testIsEventGroupLogged()
    {
        $config = ['enterprise_checkout' => 1, 'customer' => 1];

        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('admin/magento_logging/actions', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
            ->willReturn('serializedConfig');

        $this->serializerMock->expects($this->once())
            ->method('unserialize')
            ->with('serializedConfig')
            ->willReturn($config);

        $this->assertTrue($this->config->isEventGroupLogged('enterprise_checkout'));
        $this->assertFalse($this->config->isEventGroupLogged('enterprise_catalogevent'));
    }

    public function testGetEventByFullActionName()
    {
        $expected = [
            'log_name' => 'configured_log_group',
            'action' => 'view',
            'expected_models' => [\Magento\Framework\Model\AbstractModel::class => []],
        ];
        $this->assertEquals($expected, $this->config->getEventByFullActionName('adminhtml_checkout_index'));
    }
}
