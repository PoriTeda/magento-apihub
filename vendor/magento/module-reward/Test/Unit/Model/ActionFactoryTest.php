<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Test\Unit\Model;

class ActionFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Reward\Model\ActionFactory
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManagerMock;

    protected function setUp()
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->objectManagerMock = $this->createMock(\Magento\Framework\ObjectManagerInterface::class);

        $this->model = $objectManager->getObject(
            \Magento\Reward\Model\ActionFactory::class,
            ['objectManager' => $this->objectManagerMock]
        );
    }

    public function testCreate()
    {
        $type = 'action_type';
        $params = ['param' => 'value'];
        $actionMock = $this->createMock(\Magento\Reward\Model\Action\AbstractAction::class);

        $this->objectManagerMock->expects($this->once())
            ->method('create')
            ->with($type, $params)
            ->willReturn($actionMock);

        $this->assertEquals($actionMock, $this->model->create($type, $params));
    }
}
