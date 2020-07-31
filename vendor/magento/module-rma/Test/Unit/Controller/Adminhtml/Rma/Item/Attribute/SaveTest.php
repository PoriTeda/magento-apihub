<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Rma\Test\Unit\Controller\Adminhtml\Rma\Item\Attribute;

use Magento\Framework\Serialize\Serializer\FormData;

/**
 * Class SaveTest
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class SaveTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Rma\Controller\Adminhtml\Rma\Item\Attribute\Save
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
     * @var \Magento\Framework\View\Element\BlockInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $blockMock;

    /**
     * @var \Magento\Backend\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $helperMock;

    /**
     * @var \Magento\CustomAttributeManagement\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $attributeHelperMock;

    /**
     * @var \Magento\Framework\App\ActionFlag|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $flagMock;

    /**
     * @var \Magento\Eav\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eavConfigMock;

    /**
     * @var \Magento\Eav\Model\Entity\Attribute\Set|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $attributeSetMock;

    /**
     * @var \Magento\Framework\Message\Manager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $messageManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $websiteFactoryMock;

    /**
     * @var FormData|\PHPUnit_Framework_MockObject_MockObject
     */
    private $formDataSerializerMock;

    /**
     * Set up before each test.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->websiteFactoryMock = $this->createPartialMock(\Magento\Store\Model\WebsiteFactory::class, ['create']);
        $this->contextMock = $this->createMock(\Magento\Backend\App\Action\Context::class);
        $this->requestMock = $this->createMock(\Magento\Framework\App\Request\Http::class);
        $this->responseMock = $this->createMock(\Magento\Framework\App\Response\Http::class);
        $this->attributeMock = $this->createMock(\Magento\Rma\Model\Item\Attribute::class);
        $this->attributeSetMock = $this->createMock(\Magento\Eav\Model\Entity\Attribute\Set::class);
        $this->entityTypeMock = $this->createMock(\Magento\Eav\Model\Entity\Type::class);
        $this->objectManagerMock = $this->createMock(\Magento\Framework\ObjectManager\ObjectManager::class);
        $this->sessionMock = $this->createMock(\Magento\Backend\Model\Session::class);
        $this->viewMock = $this->createMock(\Magento\Framework\App\View::class);
        $this->helperMock = $this->createMock(\Magento\Backend\Helper\Data::class);
        $this->attributeHelperMock = $this->createMock(\Magento\CustomAttributeManagement\Helper\Data::class);
        $this->flagMock = $this->createMock(\Magento\Framework\App\ActionFlag::class);
        $this->eavConfigMock = $this->createMock(\Magento\Eav\Model\Config::class);
        $this->messageManagerMock = $this->createMock(\Magento\Framework\Message\Manager::class);
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
            ->will($this->returnValue($this->requestMock));
        $this->contextMock->expects($this->atLeastOnce())
            ->method('getHelper')
            ->will($this->returnValue($this->helperMock));
        $this->contextMock->expects($this->atLeastOnce())
            ->method('getResponse')
            ->will($this->returnValue($this->responseMock));
        $this->contextMock->expects($this->atLeastOnce())
            ->method('getSession')
            ->will($this->returnValue($this->sessionMock));
        $this->contextMock->expects($this->atLeastOnce())
            ->method('getObjectManager')
            ->will($this->returnValue($this->objectManagerMock));
        $this->contextMock->expects($this->atLeastOnce())
            ->method('getView')
            ->will($this->returnValue($this->viewMock));
        $this->contextMock->expects($this->atLeastOnce())
            ->method('getActionFlag')
            ->willReturn($this->flagMock);
        $this->contextMock->expects($this->atLeastOnce())
            ->method('getMessageManager')
            ->willReturn($this->messageManagerMock);
        $this->formDataSerializerMock = $this->getMockBuilder(FormData::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->action = $this->objectManager->getObject(
            \Magento\Rma\Controller\Adminhtml\Rma\Item\Attribute\Save::class,
            [
                'context' => $this->contextMock,
                'websiteFactory' => $this->websiteFactoryMock,
                'formDataSerializer' => $this->formDataSerializerMock,
            ]
        );
    }

    /**
     * Test for execute method.
     *
     * @return void
     */
    public function testExecute()
    {
        $this->requestMock->expects($this->once())
            ->method('getPostValue')
            ->willReturn([
                'frontend_input'=> '',
            ]);
        $this->requestMock
            ->method('getParam')
            ->willReturnMap([
                ['serialized_options', '[]', ''],
            ]);
        $this->formDataSerializerMock
            ->expects($this->once())
            ->method('unserialize')
            ->with('')
            ->willReturn([]);
        $this->attributeHelperMock->expects($this->once())
            ->method('filterPostData')
            ->willReturn(['frontend_input' => 'frontend_input']);
        $this->attributeHelperMock->expects($this->once())
            ->method('checkValidateRules')
            ->willReturn([]);
        $this->attributeHelperMock->expects($this->once())
            ->method('getAttributeBackendModelByInputType')
            ->willReturn('AttributeBackendModelByInputType');
        $this->attributeHelperMock->expects($this->once())
            ->method('getAttributeSourceModelByInputType')
            ->willReturn('AttributeSourceModelByInputType');
        $this->attributeHelperMock->expects($this->once())
            ->method('getAttributeBackendTypeByInputType')
            ->willReturn('AttributeBackendTypeByInputType');

        $this->eavConfigMock->expects($this->once())
            ->method('getEntityType')
            ->with('rma_item')
            ->willReturn($this->entityTypeMock);
        $this->entityTypeMock->expects($this->once())
            ->method('getDefaultAttributeSetId')
            ->willReturn(1);
        $this->requestMock->expects($this->once())
            ->method('isPost')
            ->willReturn(true);
        $this->objectManagerMock->expects($this->any())
            ->method('create')
            ->will(
                $this->returnValueMap(
                    [
                        [\Magento\Rma\Model\Item\Attribute::class, [], $this->attributeMock],
                        [\Magento\Eav\Model\Entity\Attribute\Set::class, [], $this->attributeSetMock],
                    ]
                )
            );

        $this->objectManagerMock->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValueMap(
                    [
                        [\Magento\CustomAttributeManagement\Helper\Data::class, $this->attributeHelperMock],
                        [\Magento\Eav\Model\Config::class, $this->eavConfigMock],
                    ]
                )
            );
        $this->messageManagerMock->expects($this->once())->method('addSuccess');
        $this->assertEmpty($this->action->execute());
    }

    /**
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function testExecuteWithOptionsDataError()
    {
        $serializedOptions = '{"key":"value"}';
        $message = "The attribute couldn't be saved due to an error. Verify your information and try again. "
            . "If the error persists, please try again later.";

        $this->requestMock
            ->expects($this->any())
            ->method('getParam')
            ->willReturnMap([
                ['serialized_options', '[]', $serializedOptions],
            ]);
        $this->messageManagerMock
            ->expects($this->once())
            ->method('addErrorMessage')
            ->with($message);

        $this->formDataSerializerMock
            ->expects($this->once())
            ->method('unserialize')
            ->with($serializedOptions)
            ->willThrowException(new \InvalidArgumentException('Some exception'));

        $this->assertEmpty($this->action->execute());
    }
}
