<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Test\Unit\Model\Mview\View;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\CatalogStaging\Model\Mview\View\SubscriptionFactory;
use Magento\Framework\Mview\View\SubscriptionFactory as FrameworkSubstrictionFactory;

class SubscriptionFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManagerMock;

    /**
     * @var \Magento\CatalogStaging\Model\Mview\View\SubscriptionFactory
     */
    protected $model;

    protected function setUp()
    {
        $objectManager = new ObjectManager($this);

        $this->objectManagerMock = $this->getMockBuilder(\Magento\Framework\ObjectManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->model = $objectManager->getObject(
            SubscriptionFactory::class,
            [
                'objectManager' => $this->objectManagerMock,
                'subscriptionModels' => [
                    'catalog_product_entity_int' => 'ProductEntityIntSubscription'
                ]
            ]
        );
    }

    public function testCreate()
    {
        $data = ['tableName' => 'catalog_product_entity_int', 'columnName' => 'entity_id'];

        $expectedData = $data;
        $expectedData['columnName'] = 'entity_id';

        $subscriptionMock = $this->getMockBuilder(\Magento\Framework\Mview\View\SubscriptionInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManagerMock->expects($this->once())
            ->method('create')
            ->with('ProductEntityIntSubscription', $expectedData)
            ->willReturn($subscriptionMock);

        $result = $this->model->create($data);
        $this->assertEquals($subscriptionMock, $result);
    }

    public function testCreateNoTableName()
    {
        $data = ['columnName' => 'entity_id'];

        $expectedData = $data;

        $subscriptionMock = $this->getMockBuilder(\Magento\Framework\Mview\View\SubscriptionInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManagerMock->expects($this->once())
            ->method('create')
            ->with(FrameworkSubstrictionFactory::INSTANCE_NAME, $expectedData)
            ->willReturn($subscriptionMock);

        $result = $this->model->create($data);
        $this->assertEquals($subscriptionMock, $result);
    }
}
