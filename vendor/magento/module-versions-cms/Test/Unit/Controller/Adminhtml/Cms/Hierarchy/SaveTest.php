<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VersionsCms\Test\Unit\Controller\Adminhtml\Cms\Hierarchy;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SaveTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $request;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $jsonHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $node;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $response;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $session;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $actionFlag;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $backendHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageManager;

    /**
     * @var \Magento\VersionsCms\Controller\Adminhtml\Cms\Hierarchy\Save
     */
    protected $saveController;

    protected function setUp()
    {
        $this->request = $this->createMock(\Magento\Framework\App\Request\Http::class);
        $this->jsonHelper = $this->createMock(\Magento\Framework\Json\Helper\Data::class);
        $this->node = $this->createMock(\Magento\VersionsCms\Model\Hierarchy\Node::class);
        $this->node->expects($this->once())->method('collectTree');
        $this->objectManagerMock = $this->createMock(\Magento\Framework\ObjectManagerInterface::class);
        $this->response = $this->createPartialMock(
            \Magento\Framework\App\ResponseInterface::class,
            ['setRedirect', 'sendResponse']
        );
        $this->messageManager = $this->createMock(\Magento\Framework\Message\Manager::class);
        $this->session = $this->createPartialMock(\Magento\Backend\Model\Session::class, ['setIsUrlNotice']);
        $this->actionFlag = $this->createPartialMock(\Magento\Framework\App\ActionFlag::class, ['get']);
        $this->backendHelper = $this->createPartialMock(\Magento\Backend\Helper\Data::class, ['getUrl']);
        $this->context = $this->createMock(\Magento\Backend\App\Action\Context::class);
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->saveController = $objectManager->getObject(
            \Magento\VersionsCms\Controller\Adminhtml\Cms\Hierarchy\Save::class,
            [
                'request' => $this->request,
                'response' => $this->response,
                'helper' => $this->backendHelper,
                'objectManager' => $this->objectManagerMock,
                'session' => $this->session,
                'actionFlag' => $this->actionFlag,
                'messageManager' => $this->messageManager
            ]
        );
    }

    /**
     * @param int $nodesDataEncoded
     * @param array $nodesData
     * @param array $post
     * @param string $path
     */
    protected function prepareTests($nodesDataEncoded, $nodesData, $post, $path)
    {
        $this->request->expects($this->atLeastOnce())->method('isPost')->willReturn(true);
        $this->request->expects($this->atLeastOnce())->method('getPostValue')->willReturn($post);

        $this->jsonHelper->expects($this->once())
            ->method('jsonDecode')
            ->with($nodesDataEncoded)
            ->willReturn($nodesData);

        $this->node->expects($this->once())->method('collectTree')->with($nodesData, []);

        $this->objectManagerMock->expects($this->atLeastOnce())
            ->method('create')
            ->with(\Magento\VersionsCms\Model\Hierarchy\Node::class)
            ->willReturn($this->node);
        $this->objectManagerMock->expects($this->once())
            ->method('get')
            ->with(\Magento\Framework\Json\Helper\Data::class)
            ->willReturn($this->jsonHelper);

        $this->response->expects($this->once())->method('setRedirect')->with($path);

        $this->session->expects($this->once())->method('setIsUrlNotice')->with(true);

        $this->actionFlag->expects($this->once())->method('get')->with('', 'check_url_settings')->willReturn(true);

        $this->backendHelper->expects($this->atLeastOnce())->method('getUrl')->with($path)->willReturn($path);
    }

    /**
     * @param int $nodesDataEncoded
     * @param array $nodesData
     * @param array $post
     * @param string $path
     *
     * @dataProvider successMessageDisplayedDataProvider
     */
    public function testSuccessMessageDisplayed($nodesDataEncoded, $nodesData, $post, $path)
    {
        $this->prepareTests($nodesDataEncoded, $nodesData, $post, $path);

        $this->messageManager->expects($this->once())->method('addSuccess')->with(__('You have saved the hierarchy.'));

        $this->saveController->execute();
    }

    /**
     * @param int $nodesDataEncoded
     * @param array $nodesData
     * @param array $post
     * @param string $path
     *
     * @dataProvider successMessageNotDisplayedDataProvider
     */
    public function testSuccessMessageNotDisplayed($nodesDataEncoded, $nodesData, $post, $path)
    {
        $this->prepareTests($nodesDataEncoded, $nodesData, $post, $path);

        $this->messageManager->expects($this->never())->method('addSuccess');

        $this->saveController->execute();
    }

    /**
     * @return array
     */
    public function successMessageDisplayedDataProvider()
    {
        return [
            [
                'nodesDataEncoded' => 1,
                'nodesData' => [
                    [
                        'node_id' => 0,
                        'label' => 'Trial node',
                        'identifier' => 'trial',
                        'meta_chapter' => 0,
                        'meta_section' => 0,
                    ],
                    [
                        'node_id' => 1,
                        'label' => 'Trial node 1',
                        'identifier' => 'trial1',
                        'meta_chapter' => 0,
                        'meta_section' => 0,
                    ]
                ],
                'post' => [
                    'nodes_data' => 1,
                ],
                'path' => 'adminhtml/*/index',
            ]
        ];
    }

    /**
     * @return array
     */
    public function successMessageNotDisplayedDataProvider()
    {
        return [
            [
                'nodesDataEncoded' => 1,
                'nodesData' => [],
                'post' => [
                    'nodes_data' => 1,
                ],
                'path' => 'adminhtml/*/index',
            ]
        ];
    }
}
