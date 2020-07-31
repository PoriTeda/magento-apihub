<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\WebsiteRestriction\Test\Unit\Observer;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RestrictWebsiteTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\WebsiteRestriction\Model\Observer\RestrictWebsite
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $restrictorMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $controllerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $dispatchResultMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestMock;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $observer;

    protected function setUp()
    {
        $this->markTestIncomplete();
        $this->configMock = $this->createMock(\Magento\WebsiteRestriction\Model\ConfigInterface::class);
        $this->observer = $this->createMock(\Magento\Framework\Event\Observer::class);
        $this->controllerMock = $this->createMock(\Magento\Framework\App\Action\Action::class);
        $this->requestMock = $this->createMock(\Magento\Framework\App\RequestInterface::class);

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getControllerAction', 'getRequest']);
        $eventMock->expects($this->once())
            ->method('getControllerAction')
            ->will(
                $this->returnValue($this->controllerMock)
            );

        $eventMock->expects($this->any())
            ->method('getRequest')
            ->will(
                $this->returnValue($this->requestMock)
            );

        $this->observer->expects($this->any())->method('getEvent')->will($this->returnValue($eventMock));

        $this->restrictorMock = $this->createMock(\Magento\WebsiteRestriction\Model\Restrictor::class);
        $this->dispatchResultMock = $this->createPartialMock(
            \Magento\Framework\DataObject::class,
            ['getCustomerLoggedIn', 'getShouldProceed']
        );

        $eventManagerMock = $this->createMock(\Magento\Framework\Event\ManagerInterface::class);
        $eventManagerMock->expects($this->once())->method('dispatch')->with(
            'websiterestriction_frontend',
            ['controller' => $this->controllerMock, 'result' => $this->dispatchResultMock]
        );

        $factoryMock = $this->createMock(\Magento\Framework\DataObject\Factory::class);
        $factoryMock->expects($this->once())
            ->method('create')
            ->with(['should_proceed' => true, 'customer_logged_in' => false])
            ->will($this->returnValue($this->dispatchResultMock));

        $this->model = new \Magento\WebsiteRestriction\Observer\RestrictWebsite(
            $this->configMock,
            $eventManagerMock,
            $this->restrictorMock,
            $factoryMock
        );
    }

    public function testExecuteSuccess()
    {
        $this->dispatchResultMock->expects($this->any())->method('getShouldProceed')->will($this->returnValue(true));
        $this->configMock->expects($this->any())->method('isRestrictionEnabled')->will($this->returnValue(true));
        $this->dispatchResultMock->expects($this->once())->method('getCustomerLoggedIn')->will($this->returnValue(1));

        $responseMock = $this->createMock(\Magento\Framework\App\ResponseInterface::class);
        $this->controllerMock->expects($this->once())->method('getResponse')->will($this->returnValue($responseMock));

        $this->restrictorMock->expects($this->once())->method('restrict')->with($this->requestMock, $responseMock, 1);
        $this->model->execute($this->observer);
    }

    public function testExecuteWithDisabledRestriction()
    {
        $this->configMock->expects($this->any())->method('isRestrictionEnabled')->will($this->returnValue(false));
        $this->restrictorMock->expects($this->never())->method('restrict');
        $this->model->execute($this->observer);
    }

    public function testExecuteWithNotShouldProceed()
    {
        $this->dispatchResultMock->expects($this->any())->method('getShouldProceed')->will($this->returnValue(false));
        $this->restrictorMock->expects($this->never())->method('restrict');
        $this->model->execute($this->observer);
    }
}
