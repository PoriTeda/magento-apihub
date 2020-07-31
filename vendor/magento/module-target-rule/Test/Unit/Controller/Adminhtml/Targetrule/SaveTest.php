<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\TargetRule\Test\Unit\Controller\Adminhtml\Targetrule;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @var \Magento\Indexer\Controller\Adminhtml\Indexer\ListAction
     */
    protected $object;

    /**
     * @var \Magento\Backend\App\Action\Context
     */
    protected $contextMock;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\Filter\Date|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dateFilter;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManagerMock;

    /**
     * @var \Magento\TargetRule\Model\Rule|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $model;

    /**
     * @var \Magento\Framework\App\Response\Http
     */
    protected $response;

    /**
     * @var \Magento\Framework\App\Request\Http|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $request;

    /**
     * @var \Magento\Framework\App\ActionFlag
     */
    protected $actionFlag;

    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $session;

    /**
     * @var \Magento\Backend\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\Message\Manager
     */
    protected $messageManager;

    /**
     * Set up test
     *
     * @return void
     */
    protected function setUp()
    {
        $this->contextMock = $this->createPartialMock(\Magento\Backend\App\Action\Context::class, [
                'getAuthorization',
                'getSession',
                'getActionFlag',
                'getAuth',
                'getView',
                'getHelper',
                'getBackendUrl',
                'getFormKeyValidator',
                'getLocaleResolver',
                'getCanUseBaseUrl',
                'getRequest',
                'getResponse',
                'getObjectManager',
                'getMessageManager'
            ]);

        $this->session = $this->createPartialMock(
            \Magento\Backend\Model\Session::class,
            ['setIsUrlNotice', 'setFormData', 'setPageData']
        );

        $this->actionFlag = $this->createPartialMock(\Magento\Framework\App\ActionFlag::class, ['get']);

        $this->response = $this->createMock(\Magento\Framework\App\Response\Http::class);
        $this->helper = $this->createPartialMock(\Magento\Backend\Helper\Data::class, ['getUrl']);
        $this->contextMock->expects($this->any())->method('getResponse')->willReturn($this->response);
        $this->contextMock->expects($this->any())->method('getHelper')->willReturn($this->helper);
        $this->objectManagerMock = $this->getMockForAbstractClass(\Magento\Framework\ObjectManagerInterface::class);
        $this->contextMock->expects($this->any())->method('getObjectManager')->willReturn($this->objectManagerMock);
        $this->contextMock->expects($this->any())->method('getSession')->willReturn($this->session);
        $this->contextMock->expects($this->any())->method('getActionFlag')->willReturn($this->actionFlag);
        $this->coreRegistry = $this->createMock(\Magento\Framework\Registry::class);
        $this->session->expects($this->any())->method('setIsUrlNotice')->willReturn(false);
        $this->actionFlag->expects($this->any())->method('get')->with('')->willReturn(false);
        $this->dateFilter = $this->createMock(\Magento\Framework\Stdlib\DateTime\Filter\Date::class);

        $this->messageManager = $this->createPartialMock(
            \Magento\Framework\Message\Manager::class,
            ['addSuccess', 'addError', 'addException']
        );

        $this->contextMock->expects($this->any())->method('getMessageManager')->willReturn($this->messageManager);

        $this->request = $this->createPartialMock(
            \Magento\Framework\App\Request\Http::class,
            ['getParam', 'isPost', 'getPostValue']
        );

        $this->model = $this->createPartialMock(
            \Magento\TargetRule\Model\Rule::class,
            ['validateData', 'getId', 'load', 'save', 'loadPost']
        );

        $this->contextMock->expects($this->any())->method('getRequest')->will($this->returnValue($this->request));
        $this->request->expects($this->any())->method('isPost')->willReturn(true);

        $this->objectManagerMock->expects($this->any())->method('create')->willReturn($this->model);
        $this->object = new \Magento\TargetRule\Controller\Adminhtml\Targetrule\Save(
            $this->contextMock,
            $this->coreRegistry,
            $this->dateFilter
        );
    }

    /**
     * Test with empty data variable
     *
     * @return void
     */
    public function testExecuteWithoutData()
    {
        $this->request->expects($this->once())
            ->method('getPostValue')
            ->willReturn([]);

        $route = 'URL';
        $this->helper->expects($this->any())->method('getUrl')->willReturn($route);

        $this->object->execute();
    }

    /**
     * Test with set data variable
     *
     * @param array $params
     * @expectedException /Exception
     * @expectedExceptionMessage Could not save target rule
     * @dataProvider executeDataProvider()
     * @return void
     */
    public function testExecuteWithData($params)
    {
        $data = [
            'param1' => 1,
            'param2' => 2,
            'rule' => [
                'conditions' => 'yes',
                'actions' => 'action'
            ],
            'from_date' => $params['date']['from_date'],
            'to_date' => $params['date']['to_date']
        ];

        $this->assertsForDates($params['date']);

        $this->request->expects($this->once())
            ->method('getPostValue')
            ->willReturn($data);

        $this->request->expects($this->any())->method('getParam')->will(
            $this->returnValueMap(
                [
                    ['back', false, $params['redirectBack']],
                    ['rule_id', null, $params['ruleId']]
                ]
            )
        );

        $this->model->expects($this->exactly($params['validateData']))
            ->method('validateData')->willReturn($params['validateResult']);
        $this->model->expects($this->exactly($params['modelLoad']))->method('load')->willReturn(true);
        $this->model->expects($this->exactly($params['getId'][0]))->method('getId')->willReturn($params['getId'][1]);
        $this->model->expects($this->exactly($params['modelLoadPostSave']))->method('loadPost')->willReturn(1);
        $this->model->expects($this->exactly($params['modelLoadPostSave']))->method('save')->willReturn(1);

        if ($params['addException'] != 0) {
            $this->model->expects($this->exactly($params['addException']))
                ->method('save')->willThrowException(new \Exception());
            $this->session->expects($this->exactly($params['setPageData']))->method('setPageData')->willReturn(1);
        }

        $this->session->expects($this->exactly($params['setFormData']))->method('setFormData')->willReturn(1);

        $route = 'URL';
        $this->helper->expects($this->any())->method('getUrl')->willReturn($route);

        $this->messageManager->expects($this->exactly($params['addSuccess']))->method('addSuccess')->willReturn(true);
        $this->messageManager->expects($this->exactly($params['addError']))->method('addError')->willReturn(true);
        $this->messageManager->expects($this->exactly($params['addException']))->method('addException')
            ->willReturn(true);

        $this->object->execute();
    }

    /**
     * Data provider for test
     */
    public function executeDataProvider()
    {
        return [
            'case1' => [[
                'redirectBack' => false,
                'ruleId' => 1,
                'getId' => [1, 1],
                'modelLoad' => 1,
                'setFormData' => 0,
                'setPageData' => 0,
                'validateData' => 1,
                'validateResult' => true,
                'modelLoadPostSave' => 1,
                'addSuccess' => 1,
                'addError' => 0,
                'addException' => 0,
                'date' => [
                    'from_date' => '2016-07-12',
                    'to_date' => '2016-08-12'
                ]
            ]],
            'case2' => [[
                'redirectBack' => false,
                'ruleId' => 1,
                'getId' => [1, 2], // expected times, mock return value
                'modelLoad' => 1,
                'setFormData' => 1,
                'setPageData' => 0,
                'validateData' => 0,
                'validateResult' => true,
                'modelLoadPostSave' => 0,
                'addSuccess' => 0,
                'addError' => 1,
                'addException' => 0,
                'date' => [
                    'from_date' => '2016-07-12',
                    'to_date' => ''
                ]
            ]],
            'case3' => [[
                'redirectBack' => false,
                'ruleId' => 1,
                'getId' => [1, 1],
                'modelLoad' => 1,
                'setFormData' => 1,
                'setPageData' => 0,
                'validateData' => 1,
                'validateResult' => [__('Validate error 1'), __('Validate error 2')],
                'modelLoadPostSave' => 0,
                'addSuccess' => 0,
                'addError' => 2,
                'addException' => 0,
                'date' => [
                    'from_date' => '',
                    'to_date' => '2016-07-12'
                ]
            ]],
            'case4' => [[
                'redirectBack' => false,
                'ruleId' => 1,
                'getId' => [1, 1],
                'modelLoad' => 1,
                'setFormData' => 1,
                'setPageData' => 0,
                'validateData' => 1,
                'validateResult' => true,
                'modelLoadPostSave' => 1,
                'addSuccess' => 0,
                'addError' => 1,
                'addException' => 1,
                'date' => [
                    'from_date' => '',
                    'to_date' => ''
                ]
            ]],
            'case5' => [[
                'redirectBack' => true,
                'ruleId' => 1,
                'getId' => [2, 1],
                'modelLoad' => 1,
                'setFormData' => 0,
                'setPageData' => 0,
                'validateData' => 1,
                'validateResult' => true,
                'modelLoadPostSave' => 1,
                'addSuccess' => 1,
                'addError' => 0,
                'addException' => 0,
                'date' => [
                    'from_date' => '',
                    'to_date' => ''
                ]
            ]]
        ];
    }

    private function assertsForDates(array $dates)
    {
        $datesCount = count($dates);
        if (!$dates['from_date']) {
            $datesCount--;
        }
        if (!$dates['to_date']) {
            $datesCount--;
        }
        $this->dateFilter
            ->expects(static::exactly($datesCount))
            ->method('filter')
            ->willReturn(static::returnArgument(0));
    }
}
