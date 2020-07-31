<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Logging\Test\Unit\Model;

use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\Request\Http;
use Magento\Framework\DataObject;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Message\MessageInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\AbstractModel as Quote;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Logging\Model\Config;
use Magento\Logging\Model\Event;
use Magento\Logging\Model\Event\Changes;
use Magento\Logging\Model\EventFactory;
use Magento\Logging\Model\Handler\Controllers;
use Magento\Logging\Model\Handler\ControllersFactory;
use Magento\Logging\Model\Handler\Models;
use Magento\Logging\Model\Processor;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProcessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Processor
     */
    private $processor;

    /**
     * @var Config|MockObject
     */
    private $config;

    /**
     * @var Models|MockObject
     */
    private $handlerModels;

    /**
     * @var Controllers|MockObject
     */
    private $controllers;

    /**
     * @var Session|MockObject
     */
    private $session;

    /**
     * @var ManagerInterface|MockObject
     */
    private $messageManager;

    /**
     * @var ObjectManagerInterface|MockObject
     */
    private $fakeObjectManager;

    /**
     * @var EventFactory|MockObject
     */
    private $eventFactory;

    /**
     * @var Http|MockObject
     */
    private $request;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var Quote|MockObject
     */
    private $quote;

    /**
     * @var Changes|MockObject
     */
    private $changes;

    protected function setUp()
    {
        $this->config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEventByFullActionName', 'isEventGroupLogged', 'getEventGroupConfig'])
            ->getMock();

        $this->handlerModels = $this->getMockBuilder(Models::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->controllers = $this->getMockBuilder(Controllers::class)
            ->disableOriginalConstructor()
            ->getMock();

        $handlerFactoryMock = $this->createPartialMock(ControllersFactory::class, ['create']);

        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSkipLoggingAction', 'setSkipLoggingAction', 'isLoggedIn'])
            ->getMock();

        $this->messageManager = $this->getMockBuilder(ManagerInterface::class)
            ->getMockForAbstractClass();
        $this->fakeObjectManager = $this->createMock(ObjectManagerInterface::class);
        $this->eventFactory = $this->createPartialMock(EventFactory::class, ['create']);
        $this->request = $this->createMock(Http::class);
        $this->logger = $this->getMockForAbstractClass(LoggerInterface::class);

        $objectManager = new ObjectManager($this);
        $this->processor = $objectManager->getObject(
            Processor::class,
            [
                'config' => $this->config,
                'modelsHandler' => $this->handlerModels,
                'authSession' => $this->session,
                'messageManager' => $this->messageManager,
                'objectManager' => $this->fakeObjectManager,
                'logger' => $this->logger,
                'handlerControllersFactory' => $handlerFactoryMock,
                'eventFactory' => $this->eventFactory,
                'request' => $this->request
            ]
        );
    }

    public function testInitActionSkipLogging()
    {
        $fullActionName = 'full_controller_action_name';
        $eventConfig = ['action' => 'init', 'group_name' => 'test_events'];
        $this->config->method('getEventByFullActionName')
            ->with(self::equalTo($fullActionName))
            ->willReturn($eventConfig);

        $this->config->method('isEventGroupLogged')
            ->with(self::equalTo('test_events'))
            ->willReturn(true);

        $sessionValue = [$fullActionName, 'full_controller_action_othername'];
        $this->session->method('getSkipLoggingAction')
            ->willReturn($sessionValue);

        $this->session->method('setSkipLoggingAction')
            ->with(self::equalTo(['1' => 'full_controller_action_othername']))
            ->willReturn(true);

        $this->processor->initAction($fullActionName, 'init');

        return $this->processor;
    }

    public function testInitActionSkipOnBack()
    {
        $fullActionName = 'full_controller_action_name';
        $eventConfig = [
            'action' => 'init',
            'group_name' => 'test_events',
            'skip_on_back' => ['adminhtml_cms_page_version_edit'],
        ];
        $this->config->method('getEventByFullActionName')
            ->with(self::equalTo($fullActionName))
            ->willReturn($eventConfig);

        $this->config->method('isEventGroupLogged')
            ->with(self::equalTo('test_events'))
            ->willReturn(true);

        $skippedLoggingAction = 'full_controller_action_othername,full_controller_action_thirdname';

        $skippedAfter = [
            'adminhtml_cms_page_version_edit',
            'full_controller_action_othername',
            'full_controller_action_thirdname',
        ];
        $this->session->method('getSkipLoggingAction')
            ->willReturn($skippedLoggingAction);
        $this->session->method('setSkipLoggingAction')
            ->with(self::equalTo($skippedAfter))
            ->willReturn(true);

        $this->processor->initAction($fullActionName, 'init');
    }

    /**
     * @depends testInitActionSkipLogging
     */
    public function testModelActionAfterSkipNextAction(Processor $processor)
    {
        /** @var AbstractModel|MockObject $model */
        $model = $this->getMockBuilder(AbstractModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        self::assertFalse($processor->modelActionAfter($model, 'save'));
    }

    public function testModelActionAfter()
    {
        $this->setUpModelActionAfter();
        $this->processor->initAction('full_controller_action_name', 'init');
        $this->assertEquals($this->processor, $this->processor->modelActionAfter($this->quote, 'save'));
    }

    public function testLogActionNotInitialized()
    {
        $loggingEvent = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods(['setAction', 'setEventCode', 'setInfo', 'setIsSuccess', 'save', 'setData', '__wakeup'])
            ->getMock();

        $loggingEvent->method('setData');

        $this->eventFactory->method('create')
            ->willReturn($loggingEvent);

        self::assertFalse($this->processor->logAction());
    }

    public function testLogActionDenied()
    {
        $fullActionName = 'full_controller_action_name';
        $eventConfig = ['action' => 'init', 'group_name' => 'test_events'];
        $this->config->method('getEventByFullActionName')
            ->with(self::equalTo($fullActionName))
            ->willReturn($eventConfig);
        $this->config->method('isEventGroupLogged')
            ->with(self::equalTo('test_events'))
            ->willReturn(true);
        $this->session->method('isLoggedIn')
            ->willReturn(false);

        $errorList = [
            $this->getMockForAbstractClass(MessageInterface::class),
            $this->getMockForAbstractClass(MessageInterface::class),
        ];
        $messages = new DataObject(['errors' => [$errorList]]);
        $this->messageManager->method('getMessages')
            ->willReturn($messages);

        $loggingEventMock = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods(['setAction', 'setEventCode', 'setInfo', 'setIsSuccess', 'save', '__wakeup'])
            ->getMock();
        $loggingEventMock->method('setAction')
            ->with(self::equalTo('init'));
        $loggingEventMock->method('setEventCode')
            ->with(self::equalTo('test_events'));
        $loggingEventMock->method('setInfo')
            ->with(self::equalTo('More permissions are needed to access this.'));
        $loggingEventMock->method('setIsSuccess')
            ->with(self::equalTo(0));

        $this->eventFactory->method('create')
            ->willReturn($loggingEventMock);
        $this->processor->initAction($fullActionName, 'denied');
    }

    /**
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function setUpModelActionAfter()
    {
        $eventGroupNode = ['expected_models' => [AbstractModel::class => []]];

        $fullActionName = 'full_controller_action_name';
        $eventConfig = [
            'action' => 'init',
            'group_name' => 'test_events',
            'skip_on_back' => ['adminhtml_cms_page_version_edit'],
            'expected_models' => [AbstractModel::class => [
                    'additional_data' => ['item_id', 'quote_id', 'new_password'],
                    'skip_data' => ['new_password', 'password', 'password_hash'],
                ],
            ],
        ];
        $this->config->method('getEventByFullActionName')
            ->with(self::equalTo($fullActionName))
            ->willReturn($eventConfig);

        $this->config->method('isEventGroupLogged')
            ->with(self::equalTo('test_events'))
            ->willReturn(true);

        $this->config->method('getEventGroupConfig')
            ->with(self::equalTo('test_events'))
            ->willReturn($eventGroupNode);

        $this->quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getDataUsingMethod', '__wakeup'])
            ->getMock();

        $this->quote->expects(self::at(0))
            ->method('getId')
            ->willReturn(1);

        $this->quote->expects(self::at(1))
            ->method('getDataUsingMethod')
            ->with(self::equalTo('item_id'))
            ->willReturn(2);

        $this->quote->expects(self::at(2))
            ->method('getDataUsingMethod')
            ->with(self::equalTo('quote_id'))
            ->willReturn(3);

        $this->quote->expects(self::at(3))
            ->method('getId')
            ->willReturn(1);

        $this->changes = $this->getMockBuilder(Changes::class)
            ->disableOriginalConstructor()
            ->setMethods(['cleanupData', 'hasDifference', 'setSourceName', 'setSourceId', 'save', '__wakeup'])
            ->getMock();

        $this->changes->method('cleanupData')
            ->with(self::equalTo(['new_password', 'password', 'password_hash']));

        $this->changes->method('hasDifference')
            ->willReturn(true);

        $this->changes->method('setSourceName')
            ->with(AbstractModel::class);

        $this->changes->method('setSourceId')
            ->with(self::equalTo(1));

        $this->handlerModels->method('modelSaveAfter')
            ->with(self::equalTo($this->quote), self::equalTo($this->processor))
            ->willReturn($this->changes);
    }

    public function testLogAction()
    {
        $this->setUpModelActionAfter();

        $messages = new DataObject(['errors' => []]);
        $this->messageManager->method('getMessages')
            ->willReturn($messages);

        $eventLogger = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getId',
                'setAction',
                'setEventCode',
                'setInfo',
                'setIsSuccess',
                'save',
                'setAdditionalInfo',
                '__wakeup',
            ])
            ->getMock();
        $eventLogger->method('setAction')
            ->with(self::equalTo('init'));
        $eventLogger->method('setEventCode')
            ->with(self::equalTo('test_events'));

        $this->eventFactory->method('create')
            ->willReturn($eventLogger);

        $this->processor->initAction('full_controller_action_name', 'init');
        $this->processor->modelActionAfter($this->quote, 'save');
        $this->processor->logAction();
    }
}
