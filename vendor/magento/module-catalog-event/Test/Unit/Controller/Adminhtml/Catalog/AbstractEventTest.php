<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogEvent\Test\Unit\Controller\Adminhtml\Catalog;

use Magento\CatalogEvent\Controller\Adminhtml\Catalog\Event;
use Magento\Framework\DataObject;
use Magento\Framework\Phrase;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class AbstractEventTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Backend\App\Action\Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextMock;

    /**
     * @var \Magento\Framework\Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registryMock;

    /**
     * @var \Magento\CatalogEvent\Model\EventFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventFactoryMock;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\Filter\DateTime|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dateTimeMock;

    /**
     * @var \Magento\Framework\ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManagerMock;

    /**
     * @var \Magento\CatalogEvent\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $helperMock;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManagerMock;

    /**
     * @var \Magento\Backend\Model\Auth|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $authMock;

    /**
     * @var \Magento\Framework\AuthorizationInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $authorizationMock;

    /**
     * @var \Magento\Framework\App\ResponseInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $responseMock;

    /**
     * @var \Magento\Backend\Model\Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $sessionMock;

    /**
     * @var \Magento\Framework\App\ActionFlag|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $actionFlagMock;

    /**
     * @var \Magento\Backend\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $backendHelperMock;

    /**
     * @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestMock;

    /**
     * @var \Magento\Framework\App\ViewInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $viewMock;

    /**
     * @var \Magento\Framework\View\Element\BlockInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $breadcrumbsBlockMock;

    /**
     * @var \Magento\Framework\View\LayoutInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $layoutMock;

    /**
     * @var \Magento\Framework\View\Element\BlockInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $menuBlockMock;

    /**
     * @var \Magento\Framework\Message\ManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageManagerMock;

    /**
     * @var \Magento\Framework\View\Element\BlockInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $switcherBlockMock;

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp()
    {
        $this->helperMock = $this->getMockBuilder(\Magento\CatalogEvent\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerMock = $this->getMockBuilder(\Magento\Framework\ObjectManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->objectManagerMock
            ->expects($this->any())
            ->method('get')
            ->with(\Magento\CatalogEvent\Helper\Data::class)
            ->willReturn($this->helperMock);

        $this->authorizationMock = $this->getMockBuilder(\Magento\Framework\AuthorizationInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->authMock = $this->getMockBuilder(\Magento\Backend\Model\Auth::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->responseMock = $this->getMockForAbstractClass(
            \Magento\Framework\App\ResponseInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['setStatusHeader', 'setRedirect', 'representJson']
        );

        $this->sessionMock = $this->getMockBuilder(\Magento\Backend\Model\Session::class)
            ->setMethods(['getEventData', 'setIsUrlNotice'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->actionFlagMock = $this->getMockBuilder(\Magento\Framework\App\ActionFlag::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->backendHelperMock = $this->getMockBuilder(\Magento\Backend\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this->getMockForAbstractClass(
            \Magento\Framework\App\RequestInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['isDispatched', 'initForward', 'setDispatched', 'isForwarded']
        );

        $this->viewMock = $this->getMockForAbstractClass(
            \Magento\Framework\App\ViewInterface::class,
            [],
            '',
            false,
            false,
            true,
            []
        );

        $this->messageManagerMock = $this->getMockBuilder(\Magento\Framework\Message\ManagerInterface::class)
            ->getMock();

        $this->contextMock = $this->getMockBuilder(\Magento\Backend\App\Action\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextMock
            ->expects($this->any())
            ->method('getObjectManager')
            ->willReturn($this->objectManagerMock);
        $this->contextMock
            ->expects($this->any())
            ->method('getAuth')
            ->willReturn($this->authMock);
        $this->contextMock
            ->expects($this->any())
            ->method('getAuthorization')
            ->willReturn($this->authorizationMock);
        $this->contextMock
            ->expects($this->any())
            ->method('getResponse')
            ->willReturn($this->responseMock);
        $this->contextMock
            ->expects($this->any())
            ->method('getSession')
            ->willReturn($this->sessionMock);
        $this->contextMock
            ->expects($this->any())
            ->method('getActionFlag')
            ->willReturn($this->actionFlagMock);
        $this->contextMock
            ->expects($this->any())
            ->method('getHelper')
            ->willReturn($this->backendHelperMock);
        $this->contextMock
            ->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->contextMock
            ->expects($this->any())
            ->method('getView')
            ->willReturn($this->viewMock);
        $this->contextMock
            ->expects($this->any())
            ->method('getMessageManager')
            ->willReturn($this->messageManagerMock);

        $this->registryMock = $this->getMockBuilder(\Magento\Framework\Registry::class)
            ->getMock();
        $this->eventFactoryMock = $this->getMockBuilder(\Magento\CatalogEvent\Model\EventFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->dateTimeMock = $this->getMockBuilder(\Magento\Framework\Stdlib\DateTime\Filter\DateTime::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManagerMock = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->getMock();

        $this->breadcrumbsBlockMock = $this->getMockForAbstractClass(
            \Magento\Framework\View\Element\BlockInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['addLink']
        );

        $this->menuBlockMock = $this->getMockForAbstractClass(
            \Magento\Framework\View\Element\BlockInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['setActive', 'getMenuModel']
        );

        $this->layoutMock = $this->getMockForAbstractClass(\Magento\Framework\View\LayoutInterface::class);

        $this->viewMock
            ->expects($this->any())
            ->method('getLayout')
            ->willReturn($this->layoutMock);

        $this->switcherBlockMock = $this->getMockBuilder(\Magento\Framework\View\Element\BlockInterface::class)
            ->setMethods(['setDefaultStoreName', 'toHtml', 'setSwitchUrl'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->layoutMock
            ->expects($this->any())
            ->method('getBlock')
            ->will(
                $this->returnValueMap(
                    [
                        ['breadcrumbs', $this->breadcrumbsBlockMock],
                        ['menu', $this->menuBlockMock],
                        ['store_switcher', $this->switcherBlockMock]
                    ]
                )
            );

        $menuModelMock = $this->getMockBuilder(\Magento\Backend\Model\Menu::class)
            ->disableOriginalConstructor()
            ->getMock();
        $menuModelMock
            ->expects($this->any())
            ->method('getParentItems')
            ->willReturn([]);

        $this->menuBlockMock
            ->expects($this->any())
            ->method('getMenuModel')
            ->willReturn($menuModelMock);
    }
}
