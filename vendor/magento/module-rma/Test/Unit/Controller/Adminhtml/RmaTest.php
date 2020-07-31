<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Rma\Test\Unit\Controller\Adminhtml;

use Magento\Framework\Session\SessionManagerInterface;

/**
 * Class RmaTest
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
abstract class RmaTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var string
     */
    protected $name = '';

    /**
     * @var \Magento\Rma\Controller\Adminhtml\Rma
     */
    protected $action;

    /**
     * @var \Magento\Framework\App\Request\Http|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestMock;

    /**
     * @var \Magento\Framework\App\Response\Http|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $responseMock;

    /**
     * @var \Magento\Framework\Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $coreRegistryMock;

    /**
     * @var \Magento\Framework\ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManagerMock;

    /**
     * @var \Magento\Framework\Message\ManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageManagerMock;

    /**
     * @var \Magento\Backend\Model\Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $sessionMock;

    /**
     * @var \Magento\Framework\App\ActionFlag|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $flagActionMock;

    /**
     * @var \Magento\Rma\Model\ResourceModel\Item\Collection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $rmaCollectionMock;

    /**
     * @var \Magento\Rma\Model\Item|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $rmaItemMock;

    /**
     * @var \Magento\Rma\Model\Rma|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $rmaModelMock;

    /**
     * @var \Magento\Sales\Model\Order|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderMock;

    /**
     * @var \Magento\Rma\Model\Rma\Source\Status|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $sourceStatusMock;

    /**
     * @var \Magento\Rma\Model\Rma\Status\History|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $statusHistoryMock;

    /**
     * @var \Magento\Framework\App\ViewInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $viewMock;

    /**
     * @var \Magento\Framework\View\Page\Title|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $titleMock;

    /**
     * @var \Magento\Framework\Data\Form|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $formMock;

    /**
     * @var \Magento\Rma\Model\Rma\RmaDataMapper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $rmaDataMapperMock;

    /**
     * @var SessionManagerInterface| \PHPUnit_Framework_MockObject_MockObject
     */
    protected $sessionManager;

    /**
     * test setUp
     */
    protected function setUp()
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $contextMock = $this->createMock(\Magento\Backend\App\Action\Context::class);
        $backendHelperMock = $this->createMock(\Magento\Backend\Helper\Data::class);
        $this->rmaDataMapperMock = $this->createMock(\Magento\Rma\Model\Rma\RmaDataMapper::class);
        $this->viewMock = $this->createMock(\Magento\Framework\App\ViewInterface::class);
        $this->titleMock = $this->createMock(\Magento\Framework\View\Page\Title::class);
        $this->formMock = $this->createPartialMock(\Magento\Framework\Data\Form::class, ['hasNewAttributes', 'toHtml']);
        $this->initMocks();
        $contextMock->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue($this->requestMock));
        $contextMock->expects($this->once())
            ->method('getResponse')
            ->will($this->returnValue($this->responseMock));
        $contextMock->expects($this->once())
            ->method('getObjectManager')
            ->will($this->returnValue($this->objectManagerMock));
        $contextMock->expects($this->once())
            ->method('getMessageManager')
            ->will($this->returnValue($this->messageManagerMock));
        $contextMock->expects($this->once())
            ->method('getSession')
            ->will($this->returnValue($this->sessionMock));
        $contextMock->expects($this->once())
            ->method('getActionFlag')
            ->will($this->returnValue($this->flagActionMock));
        $contextMock->expects($this->once())
            ->method('getHelper')
            ->will($this->returnValue($backendHelperMock));
        $contextMock->expects($this->once())
            ->method('getView')
            ->will($this->returnValue($this->viewMock));
        $this->sessionManager= $this->createMock(SessionManagerInterface::class);
        $arguments = $this->getConstructArguments();
        $arguments['context'] = $contextMock;
        $arguments['sessionManager'] = $this->sessionManager;

        $this->action = $objectManager->getObject(
            'Magento\\Rma\\Controller\\Adminhtml\\Rma\\' . $this->name,
            $arguments
        );
    }

    /**
     * @return array
     */
    protected function getConstructArguments()
    {
        return [
            'coreRegistry' => $this->coreRegistryMock,
            'rmaDataMapper' => $this->rmaDataMapperMock
        ];
    }

    protected function initMocks()
    {
        $this->coreRegistryMock = $this->createMock(\Magento\Framework\Registry::class);
        $this->requestMock = $this->createMock(\Magento\Framework\App\Request\Http::class);
        $this->responseMock = $this->createPartialMock(\Magento\Framework\App\Response\Http::class, [
                'setBody',
                'representJson',
                'setRedirect',
                '__wakeup'
            ]);
        $this->objectManagerMock = $this->createMock(\Magento\Framework\ObjectManagerInterface::class);
        $this->messageManagerMock = $this->createMock(\Magento\Framework\Message\ManagerInterface::class);
        $this->sessionMock = $this->createMock(\Magento\Backend\Model\Session::class);
        $this->flagActionMock = $this->createMock(\Magento\Framework\App\ActionFlag::class);
        $this->rmaCollectionMock = $this->createMock(\Magento\Rma\Model\ResourceModel\Item\Collection::class);
        $this->rmaItemMock = $this->createMock(\Magento\Rma\Model\Item::class);
        $this->rmaModelMock = $this->createPartialMock(\Magento\Rma\Model\Rma::class, [
                'saveRma',
                'getId',
                'setStatus',
                'load',
                'canClose',
                'close',
                'save',
                '__wakeup'
            ]);
        $this->orderMock = $this->createMock(\Magento\Sales\Model\Order::class);
        $this->sourceStatusMock = $this->createMock(\Magento\Rma\Model\Rma\Source\Status::class);
        $this->statusHistoryMock = $this->createPartialMock(\Magento\Rma\Model\Rma\Status\History::class, [
                'setRma',
                'setRmaEntityId',
                'sendNewRmaEmail',
                'saveComment',
                'saveSystemComment',
                'setComment',
                'sendAuthorizeEmail',
                'sendCommentEmail',
                '__wakeup'
            ]);
        $this->objectManagerMock->expects($this->any())
            ->method('create')
            ->will(
                $this->returnValueMap(
                    [
                        [\Magento\Rma\Model\ResourceModel\Item\Collection::class, [], $this->rmaCollectionMock],
                        [\Magento\Rma\Model\Item::class, [], $this->rmaItemMock],
                        [\Magento\Rma\Model\Rma::class, [], $this->rmaModelMock],
                        [\Magento\Sales\Model\Order::class, [], $this->orderMock],
                        [\Magento\Rma\Model\Rma\Source\Status::class, [], $this->sourceStatusMock],
                        [\Magento\Rma\Model\Rma\Status\History::class, [], $this->statusHistoryMock],
                        [SessionManagerInterface::class], [], $this->sessionManager
                    ]
                )
            );
    }

    protected function initRequestData($commentText = '', $visibleOnFront = true)
    {
        $rmaConfirmation = true;
        $post = [
            'items' => [],
            'rma_confirmation' => $rmaConfirmation,
            'comment' => [
                'comment' => $commentText,
                'is_visible_on_front' => $visibleOnFront,
            ],
        ];
        $this->requestMock->expects($this->once())
            ->method('isPost')
            ->will($this->returnValue(true));
        $this->requestMock->expects($this->once())
            ->method('getPostValue')
            ->will(
                $this->returnValue(
                    [
                        'items' => [],
                        'rma_confirmation' => $rmaConfirmation,
                        'comment' => [
                            'comment' => $commentText,
                            'is_visible_on_front' => $visibleOnFront,
                        ],
                    ]
                )
            );
        return $post;
    }
}
