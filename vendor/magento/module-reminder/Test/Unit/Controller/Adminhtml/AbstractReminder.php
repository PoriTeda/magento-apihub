<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Reminder\Test\Unit\Controller\Adminhtml;

/**
 * Class AbstractReminder
 * @package Magento\Reminder\Test\Unit\Controller\Adminhtml\Reminder
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
abstract class AbstractReminder extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\App\Action\Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * @var \Magento\Backend\Model\Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $session;

    /**
     * @var \Magento\Framework\App\ResponseInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $response;

    /**
     * @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $request;

    /**
     * @var \Magento\Framework\Message\Manager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageManager;

    /**
     * @var \Magento\Reminder\Model\RuleFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $ruleFactory;

    /**
     * @var \Magento\Reminder\Model\Rule|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $rule;

    /**
     * @var \Magento\Framework\Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $coreRegistry;

    /**
     * @var \Magento\Backend\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $backendHelper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\Filter\DateTime|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dataFilter;

    /**
     * @var \Magento\Reminder\Model\Rule\ConditionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $conditionFactory;

    /**
     * @var \Magento\Framework\App\ViewInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $view;

    /**
     * @var \Magento\Framework\View\LayoutInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $layout;

    /**
     * @var \Magento\Framework\View\Element\BlockInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $block;

    /**
     * @var \Magento\Backend\Model\Menu|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $menuModel;

    /**
     * @var \Magento\Framework\View\Result\Page|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $page;

    /**
     * @var \Magento\Framework\View\Page\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $config;

    /**
     * @var \Magento\Framework\View\Page\Title|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $titleMock;

    /**
     * @var \Magento\Backend\Model\Menu\Item|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $item;

    /**
     * @var \Magento\Reminder\Model\Rule\ConditionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $condition;

    /**
     * @var \Psr\Log\LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $logger;

    /**
     * @var \Magento\Framework\ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManagerMock;

    /**
     * @var \Magento\Framework\App\ActionFlag|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $actionFlag;

    /**
     * @var \Magento\Backend\Model\View\Result\RedirectFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultRedirectFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timeZoneResolver;

    protected function setUp()
    {
        $this->objectManagerMock = $this->createMock(\Magento\Framework\ObjectManagerInterface::class);
        $this->titleMock =  $this->createMock(\Magento\Framework\View\Page\Title::class);
        $this->logger =  $this->getMockForAbstractClass(\Psr\Log\LoggerInterface::class, [], '', false);
        $this->actionFlag = $this->createMock(\Magento\Framework\App\ActionFlag::class);

        $this->response = $this->createPartialMock(
            \Magento\Framework\App\ResponseInterface::class,
            ['setRedirect', 'sendResponse', 'setBody']
        );
        $this->request = $this->createMock(\Magento\Framework\App\Request\Http::class);
        $this->messageManager = $this->createMock(\Magento\Framework\Message\Manager::class);

        $this->resultRedirectFactory = $this->createPartialMock(
            \Magento\Backend\Model\View\Result\RedirectFactory::class,
            ['create']
        );

        $this->session = $this->createPartialMock(
            \Magento\Backend\Model\Session::class,
            ['setIsUrlNotice', 'setPageData', 'getPageData', 'setFormData']
        );
        $this->dataFilter = $this->createMock(\Magento\Framework\Stdlib\DateTime\Filter\DateTime::class);
        $this->conditionFactory = $this->createMock(\Magento\Reminder\Model\Rule\ConditionFactory::class);
        $this->ruleFactory = $this->createPartialMock(\Magento\Reminder\Model\RuleFactory::class, ['create']);

        $this->rule = $this->createPartialMock(
            \Magento\Reminder\Model\Rule::class,
            [
                'getData',
                'getName',
                'convertConfigTimeToUtc',
                'getId',
                'validateData',
                'save',
                'delete',
                'load',
                'setData',
                'getConditions',
                'addData',
                'sendReminderEmails',
                'loadPost'
            ]
        );
        $this->backendHelper = $this->createMock(\Magento\Backend\Helper\Data::class);
        $this->coreRegistry = $this->createMock(\Magento\Framework\Registry::class);

        $this->view = $this->getMockForAbstractClass(\Magento\Framework\App\ViewInterface::class, [], '', false);

        $this->layout = $this->getMockForAbstractClass(\Magento\Framework\View\LayoutInterface::class, [], '', false);
        $this->block = $this->createPartialMock(
            \Magento\Framework\View\Element\BlockInterface::class,
            ['setActive', 'toHtml', 'getMenuModel', 'addLink', 'setData']
        );
        $this->condition = $this->createMock(\Magento\Rule\Model\Condition\Combine::class);
        $this->menuModel = $this->createMock(\Magento\Backend\Model\Menu::class);
        $this->page = $this->createMock(\Magento\Framework\View\Result\Page::class);
        $this->config = $this->createMock(\Magento\Framework\View\Page\Config::class);
        $this->item = $this->createMock(\Magento\Backend\Model\Menu\Item::class);

        $this->context = $this->createMock(\Magento\Backend\App\Action\Context::class);
        $this->context->expects($this->once())->method('getRequest')->willReturn($this->request);
        $this->context->expects($this->once())->method('getResponse')->willReturn($this->response);
        $this->context->expects($this->once())->method('getObjectManager')->willReturn($this->objectManagerMock);
        $this->context->expects($this->once())->method('getView')->willReturn($this->view);
        $this->context->expects($this->once())->method('getSession')->willReturn($this->session);
        $this->context->expects($this->once())->method('getResponse')->willReturn($this->response);
        $this->context->expects($this->once())->method('getMessageManager')->willReturn($this->messageManager);
        $this->context->expects($this->once())->method('getHelper')->willReturn($this->backendHelper);
        $this->context->expects($this->once())
            ->method('getResultRedirectFactory')
            ->willReturn($this->resultRedirectFactory);
        $this->context->expects($this->once())->method('getActionFlag')->willReturn($this->actionFlag);

        $this->timeZoneResolver = $this->createMock(\Magento\Framework\Stdlib\DateTime\TimezoneInterface::class);
    }

    protected function initRuleWithException()
    {
        $this->request->expects($this->at(0))->method('getParam')->willReturn(1);
        $this->ruleFactory->expects($this->once())->method('create')->willReturn($this->rule);
        $this->rule->expects($this->any())->method('getId')->willReturn(null);

        $this->coreRegistry->expects($this->never())
            ->method('register')->with('current_reminder_rule', $this->rule)->willReturn(1);
    }

    protected function initRule()
    {
        $this->request->expects($this->at(0))->method('getParam')->willReturn(1);
        $this->ruleFactory->expects($this->once())->method('create')->willReturn($this->rule);
        $this->rule->expects($this->any())->method('getId')->willReturn(1);
        $this->rule->expects($this->any())->method('load')->willReturnSelf();
        $this->coreRegistry->expects($this->any())
                ->method('register')->with('current_reminder_rule', $this->rule);
    }

    protected function initRuleWithDate()
    {
        $this->request->expects($this->at(0))->method('getParam')->willReturn(1);
        $this->ruleFactory->expects($this->once())->method('create')->willReturn($this->rule);
        $this->rule->expects($this->atLeastOnce())->method('getId')->willReturn(1);

        $getDataMap = [
            ['from_date', null, '2015-12-19 00:00:00'],
            ['to_date', null, '2015-12-21 00:00:00']
        ];

        $this->rule->expects($this->atLeastOnce())
            ->method('getData')
            ->will($this->returnValueMap($getDataMap));

        $dateFormatMap = [
            [
                '2015-12-19 00:00:00',
                \IntlDateFormatter::SHORT,
                \IntlDateFormatter::SHORT,
                null,
                null,
                null,
                '2015-12-19 08:00:00'
            ],
            [
                '2015-12-21 00:00:00',
                \IntlDateFormatter::SHORT,
                \IntlDateFormatter::SHORT,
                null,
                null,
                null,
                '2015-12-21 08:00:00']
        ];

        $this->timeZoneResolver->expects($this->atLeastOnce())
            ->method('formatDateTime')
            ->will($this->returnValueMap($dateFormatMap));
        $this->rule->expects($this->atLeastOnce())
            ->method('setData')
            ->willReturn($this->returnSelf());
        $this->coreRegistry->expects($this->any())
            ->method('register')
            ->with('current_reminder_rule', $this->rule);
    }

    protected function redirect($path, $args = [])
    {
        $this->actionFlag->expects($this->any())->method('get');
        $this->session->expects($this->any())->method('setIsUrlNotice');
        $this->response->expects($this->once())->method('setRedirect');
        $this->backendHelper->expects($this->once())->method('getUrl')->with($path, $args);
    }
}
