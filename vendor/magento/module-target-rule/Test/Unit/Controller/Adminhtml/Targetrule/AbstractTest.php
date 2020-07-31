<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\TargetRule\Test\Unit\Controller\Adminhtml\Targetrule;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class AbstractTest extends \PHPUnit\Framework\TestCase
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
     * @var \Magento\Framework\Stdlib\DateTime\Filter\Date|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dateMock;

    /**
     * @var \Magento\Framework\ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManagerMock;

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
     * @var \Magento\Framework\Message\ManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageManagerMock;

    /**
     * @var \Magento\Framework\View\LayoutInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $layoutMock;

    /**
     * @var \Magento\Framework\View\Element\BlockInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $breadcrumbsBlockMock;

    /**
     * @var \Magento\Framework\View\Element\BlockInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $menuBlockMock;

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
        $this->contextMock = $this->getMockBuilder(\Magento\Backend\App\Action\Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerMock = $this->getMockBuilder(\Magento\Framework\ObjectManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->authorizationMock = $this->getMockBuilder(\Magento\Framework\AuthorizationInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->authMock = $this->getMockBuilder(\Magento\Backend\Model\Auth::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->responseMock = $this->getMockBuilder(\Magento\Framework\App\ResponseInterface::class)
            ->setMethods(['setStatusHeader', 'setRedirect', 'representJson', 'setBody', 'sendResponse'])
            ->getMock();
        $this->sessionMock = $this->getMockBuilder(\Magento\Backend\Model\Session::class)
            ->setMethods(['getEventData', 'setIsUrlNotice', 'getFormData'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->actionFlagMock = $this->getMockBuilder(\Magento\Framework\App\ActionFlag::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->backendHelperMock = $this->getMockBuilder(\Magento\Backend\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->setMethods(['isDispatched', 'initForward', 'setDispatched', 'isForwarded'])
            ->getMockForAbstractClass();

        $this->viewMock = $this->getMockBuilder(\Magento\Framework\App\ViewInterface::class)
            ->getMock();

        $this->breadcrumbsBlockMock = $this->getMockBuilder(\Magento\Framework\View\Element\BlockInterface::class)
            ->setMethods(['addLink', 'toHtml'])
            ->getMock();

        $this->menuBlockMock = $this->getMockBuilder(\Magento\Framework\View\Element\BlockInterface::class)
            ->setMethods(['setActive', 'getMenuModel', 'toHtml'])
            ->getMock();

        $this->switcherBlockMock = $this->getMockBuilder(\Magento\Framework\View\Element\BlockInterface::class)
            ->setMethods(['setDefaultStoreName', 'toHtml', 'setSwitchUrl'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->layoutMock = $this->getMockBuilder(\Magento\Framework\View\LayoutInterface::class)
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

        $this->viewMock
            ->expects($this->any())
            ->method('getLayout')
            ->willReturn($this->layoutMock);

        $this->registryMock = $this->getMockBuilder(\Magento\Framework\Registry::class)
            ->getMock();
        $this->dateMock = $this->getMockBuilder(\Magento\Framework\Stdlib\DateTime\Filter\Date::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->backendHelperMock
            ->expects($this->any())
            ->method('getUrl')
            ->willReturnArgument(0);
    }

    /**
     * @param string $className
     * @param string $prefix
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockForConditionsHtmlAction($className, $prefix)
    {
        $ruleMock = $this->getMockBuilder(\Magento\TargetRule\Model\Rule::class)
            ->disableOriginalConstructor()
            ->getMock();

        $conditionMock = $this->getMockBuilder($className)
            ->setMethods(
                ['setId', 'setType', 'setRule', 'setPrefix', 'setAttribute', 'setJsFormObject', 'asHtmlRecursive']
            )
            ->disableOriginalConstructor()
            ->getMock();
        $conditionMock
            ->expects($this->atLeastOnce())
            ->method('setId')
            ->with(123)
            ->willReturnSelf();
        $conditionMock
            ->expects($this->atLeastOnce())
            ->method('setType')
            ->with('foo')
            ->willReturnSelf();
        $conditionMock
            ->expects($this->atLeastOnce())
            ->method('setRule')
            ->with($ruleMock)
            ->willReturnSelf();
        $conditionMock
            ->expects($this->atLeastOnce())
            ->method('setAttribute')
            ->with('bar');
        $conditionMock
            ->expects($this->atLeastOnce())
            ->method('setPrefix')
            ->with($prefix)
            ->willReturnSelf();

        $this->objectManagerMock
            ->expects($this->exactly(2))
            ->method('create')
            ->withConsecutive(
                ['foo', []],
                [\Magento\TargetRule\Model\Rule::class, []]
            )
            ->willReturnOnConsecutiveCalls(
                $conditionMock,
                $ruleMock
            );

        return $conditionMock;
    }
}
