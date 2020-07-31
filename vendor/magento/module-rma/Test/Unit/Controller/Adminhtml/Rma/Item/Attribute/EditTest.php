<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Rma\Test\Unit\Controller\Adminhtml\Rma\Item\Attribute;

/**
 * Class Edit Test
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class EditTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Rma\Controller\Adminhtml\Rma\Item\Attribute\Edit
     */
    protected $action;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \Magento\Backend\App\Action\Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextMock;

    /**
     * @var \Magento\Framework\App\Request\Http|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestMock;

    /**
     * @var \Magento\Framework\App\Response\Http|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $responseMock;

    /**
     * @var \Magento\Framework\ObjectManager\ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectManagerMock;

    /**
     * @var \Magento\Rma\Model\Item\Attribute|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $attributeMock;

    /**
     * @var \Magento\Eav\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eavConfigMock;

    /**
     * @var \Magento\Eav\Model\Entity\Type|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityTypeMock;

    /**
     * @var \Magento\Backend\Model\Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $sessionMock;

    /**
     * @var \Magento\Framework\App\View|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $viewMock;

    /**
     * @var \Magento\Framework\View\Layout|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $layoutMock;

    /**
     * @var \Magento\Framework\View\Element\BlockInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $blockMock;

    /**
     * @var \Magento\Store\Model\WebsiteFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $websiteFactoryMock;

    /**
     * @var \Magento\Framework\Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $coreRegistryMock;

    /**
     * @var \Magento\Framework\Message\Manager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageManagerMock;

    /**
     * Setup before each test
     * @return void
     */
    protected function setUp()
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->websiteFactoryMock = $this->createPartialMock(\Magento\Store\Model\WebsiteFactory::class, ['create']);
        $this->coreRegistryMock = $this->createMock(\Magento\Framework\Registry::class);
        $this->contextMock = $this->createMock(\Magento\Backend\App\Action\Context::class);
        $this->requestMock = $this->createMock(\Magento\Framework\App\Request\Http::class);
        $this->responseMock = $this->createMock(\Magento\Framework\App\Response\Http::class);
        $this->attributeMock = $this->createPartialMock(
            \Magento\Rma\Model\Item\Attribute::class,
            ['load', 'setWebsite', 'setEntityTypeId', 'getId', 'getFrontendLabel', 'getEntityTypeId']
        );
        $this->eavConfigMock = $this->createMock(\Magento\Eav\Model\Config::class);
        $this->entityTypeMock = $this->createMock(\Magento\Eav\Model\Entity\Type::class);
        $this->objectManagerMock = $this->createMock(\Magento\Framework\ObjectManager\ObjectManager::class);
        $this->messageManagerMock = $this->createMock(\Magento\Framework\Message\Manager::class);
        $this->sessionMock = $this->createMock(\Magento\Backend\Model\Session::class);
        $this->viewMock = $this->createMock(\Magento\Framework\App\View::class);
        $this->layoutMock = $this->createMock(\Magento\Framework\View\Layout::class);
        $this->blockMock = $this->getMockForAbstractClass(
            \Magento\Framework\View\Element\BlockInterface::class,
            [],
            '',
            false,
            false,
            true,
            ['setActive', 'getMenuModel', 'getParentItems', 'addLink', 'getConfig', 'getTitle', 'prepend']
        );
        $this->contextMock->expects($this->atLeastOnce())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->contextMock->expects($this->atLeastOnce())
            ->method('getResponse')
            ->willReturn($this->responseMock);
        $this->contextMock->expects($this->atLeastOnce())
            ->method('getSession')
            ->willReturn($this->sessionMock);
        $this->contextMock->expects($this->atLeastOnce())
            ->method('getObjectManager')
            ->willReturn($this->objectManagerMock);
        $this->contextMock->expects($this->atLeastOnce())
            ->method('getMessageManager')
            ->willReturn($this->messageManagerMock);
        $this->contextMock->expects($this->atLeastOnce())
            ->method('getActionFlag')
            ->willReturn($this->createMock(\Magento\Framework\App\ActionFlag::class));
        $this->contextMock->expects($this->atLeastOnce())
            ->method('getHelper')
            ->willReturn($this->createMock(\Magento\Backend\Helper\Data::class));
        $this->contextMock->expects($this->atLeastOnce())
            ->method('getView')
            ->willReturn($this->viewMock);
        $this->action = $this->objectManager->getObject(
            \Magento\Rma\Controller\Adminhtml\Rma\Item\Attribute\Edit::class,
            [
                'context' => $this->contextMock,
                'coreRegistry' => $this->coreRegistryMock,
                'websiteFactory' => $this->websiteFactoryMock
            ]
        );
    }

    public function mockInitAttribute($website = null)
    {
        //_initAttribute
        $this->objectManagerMock->expects($this->once())
            ->method('create')
            ->with(\Magento\Rma\Model\Item\Attribute::class, [])
            ->willReturn($this->attributeMock);
        if ($website) {
            $this->websiteFactoryMock->expects($this->never())
                ->method('create');
        } else {
            $website = new \Magento\Framework\DataObject();
            $this->websiteFactoryMock->expects($this->once())
                ->method('create')
                ->willReturn($website);
        }
        $this->attributeMock->expects($this->once())
            ->method('setWebsite')
            ->with($website)
            ->willReturnSelf();

        //_getEntityType
        $this->objectManagerMock->expects($this->once())
            ->method('get')
            ->with(\Magento\Eav\Model\Config::class)
            ->willReturn($this->eavConfigMock);
        $this->eavConfigMock->expects($this->once())
            ->method('getEntityType')
            ->with('rma_item')
            ->willReturn($this->entityTypeMock);

        $this->attributeMock->expects($this->once())
            ->method('setEntityTypeId')
            ->willReturnSelf();
    }

    /**
     * Test for method execute.
     *
     * @dataProvider executeDataProvider
     * @param int|null $attributeId
     * @param int|null $website
     * @param string $expectedLabel
     * @param string $expectedTitle
     */
    public function testExecute($attributeId, $website, $expectedLabel, $expectedTitle)
    {
        $requestParameters = [
            ['attribute_id', null, $attributeId],
            ['website', null, $website]
        ];
        $this->requestMock->expects($this->exactly(2))
            ->method('getParam')
            ->willReturnMap($requestParameters);

        $this->mockInitAttribute($website);

        if ($attributeId) {
            $this->attributeMock->expects($this->once())
                ->method('load')
                ->with($attributeId)
                ->willReturnSelf();
            $this->attributeMock->expects($this->once())
                ->method('getFrontendLabel')
                ->willReturn(__($expectedTitle));
        } else {
            $this->attributeMock->expects($this->never())
                ->method('load');
        }
        $this->attributeMock->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn($attributeId);

        $this->coreRegistryMock->expects($this->once())
            ->method('register')
            ->with('entity_attribute', $this->attributeMock);

        $this->layoutMock->expects($this->atLeastOnce())
            ->method('getBlock')
            ->willReturn($this->blockMock);
        $this->viewMock->expects($this->atLeastOnce())
            ->method('getLayout')
            ->willReturn($this->layoutMock);
        $this->viewMock->expects($this->atLeastOnce())
            ->method('getPage')
            ->willReturn($this->blockMock);
        $this->viewMock->expects($this->once())
            ->method('renderLayout');
        $this->blockMock->expects($this->atLeastOnce())
            ->method('getConfig')
            ->willReturn($this->blockMock);
        $this->blockMock->expects($this->atLeastOnce())
            ->method('getTitle')
            ->willReturn($this->blockMock);
        $this->blockMock->expects($this->atLeastOnce())
            ->method('getMenuModel')
            ->willReturn($this->blockMock);
        $this->blockMock->expects($this->atLeastOnce())
            ->method('getParentItems')
            ->willReturn([]);
        $links = [];
        $this->blockMock->expects($this->atLeastOnce())
            ->method('addLink')
            ->with($this->callback(function ($value) use (&$links) {
                $links[] = $value->__toString();
                return true;
            }));
        $titles = [];
        $this->blockMock->expects($this->atLeastOnce())
            ->method('prepend')
            ->with($this->callback(function ($value) use (&$titles) {
                $titles[] = $value->__toString();
                return true;
            }));

        $this->assertEmpty($this->action->execute());
        $this->assertContains($expectedLabel, $links);
        $this->assertContains($expectedTitle, $titles);
    }

    public function executeDataProvider()
    {
        return [
            [null, null, 'New Return Item Attribute', 'New Return Attribute'],
            [null, 1, 'New Return Item Attribute', 'New Return Attribute'],
            [109, null, 'Edit Return Item Attribute', 'Return Attribute #109'],
            [111, 1, 'Edit Return Item Attribute', 'Return Attribute #111'],
        ];
    }

    /**
     * Test for execute method. Attribute is no longer exist.
     */
    public function testExecuteErrorNoId()
    {
        $attributeId = 1;
        $requestParameters = [
            ['attribute_id', null, $attributeId],
            ['website', null, null]
        ];
        $this->requestMock->expects($this->exactly(2))
            ->method('getParam')
            ->willReturnMap($requestParameters);

        $this->mockInitAttribute();

        $this->attributeMock->expects($this->once())
            ->method('load')
            ->with($attributeId)
            ->willReturnSelf();
        $this->attributeMock->expects($this->once())
            ->method('getId')
            ->willReturn(null);
        $this->messageManagerMock->expects($this->once())
            ->method('addError')
            ->with(__('This attribute no longer exists.'));

        $this->assertEmpty($this->action->execute());
    }

    /**
     * Test for execute method. Entity type is wrong.
     */
    public function testExecuteErrorWrongEntityType()
    {
        $attributeId = 111;
        $entityTypeId = 1;
        $requestParameters = [
            ['attribute_id', null, $attributeId],
            ['website', null, null]
        ];
        $this->requestMock->expects($this->exactly(2))
            ->method('getParam')
            ->willReturnMap($requestParameters);

        $this->mockInitAttribute();

        $this->attributeMock->expects($this->once())
            ->method('load')
            ->with($attributeId)
            ->willReturnSelf();
        $this->attributeMock->expects($this->once())
            ->method('getId')
            ->willReturn($attributeId);
        $this->attributeMock->expects($this->once())
            ->method('getEntityTypeId')
            ->willReturn($entityTypeId);
        $this->entityTypeMock->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn($entityTypeId + 1);
        $this->messageManagerMock->expects($this->once())
            ->method('addError')
            ->with(__('You cannot edit this attribute.'));

        $this->assertEmpty($this->action->execute());
    }
}
