<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PromotionPermissions\Test\Unit\Observer;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

/**
 * Class ControllerActionPredispatchObserverTest
 * @package Magento\PromotionPermissions\Test\Unit\Observer
 */
class ControllerActionPredispatchObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\PromotionPermissions\Observer\ControllerActionPredispatchObserver
     */
    private $model;

    /**
     * @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $request;

    /**
     * @var \Magento\Framework\Event\Observer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $observer;

    /**
     * @var \Magento\SalesRule\Controller\Adminhtml\Promo\Quote|\PHPUnit_Framework_MockObject_MockObject
     */
    private $controllerAction;

    /**
     * @var \Magento\PromotionPermissions\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    private $promoPermData;

    protected function setUp()
    {
        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->request =  $this->getMockForAbstractClass(
            \Magento\Framework\App\RequestInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['initForward', 'setDispatched']
        );
        $this->promoPermData =  $this->getMockBuilder(\Magento\PromotionPermissions\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->promoPermData->method('getCanAdminEditSalesRules')->willReturn(false);
        $this->observer = $this->getMockBuilder(\Magento\Framework\Event\Observer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getControllerAction'])
            ->getMock();
        $this->controllerAction = $this->getMockBuilder(\Magento\SalesRule\Controller\Adminhtml\Promo\Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->model = $objectManagerHelper->getObject(
            \Magento\PromotionPermissions\Observer\ControllerActionPredispatchObserver::class,
            [
                'promoPermData' => $this->promoPermData,
                'request' => $this->request,
            ]
        );
    }

    /**
     * @param $actionName
     * @dataProvider dataProvider
     */
    public function testExecute($actionName)
    {
        $this->observer->expects($this->once())
            ->method('getControllerAction')
            ->willReturn($this->controllerAction);
        $this->request->expects($this->any())
            ->method('getActionName')
            ->willReturn($actionName);
        $this->request->expects($this->once())
            ->method('setActionName')
            ->willReturnSelf();

        $this->model->execute($this->observer);
    }

    /**
     * @return array
     */
    public function dataProvider()
    {
        return [
            ['DELETE'],
            ['delete']
        ];
    }
}
