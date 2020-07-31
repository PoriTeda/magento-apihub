<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Test\Unit\Block\Adminhtml\Rma\Edit;

/**
 * Class GridTest
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ItemTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $formFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $coreRegistryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $rmaDataMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $itemFormFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $itemFactoryMock;

    /**
     * @var \Magento\Rma\Block\Adminhtml\Rma\Edit\Item
     */
    protected $item;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $layoutMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $escaperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $urlBuilderMock;

    /**
     * Test setUp
     */
    protected function setUp()
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->formFactoryMock = $this->createPartialMock(\Magento\Framework\Data\FormFactory::class, ['create']);
        $this->coreRegistryMock = $this->createMock(\Magento\Framework\Registry::class);
        $this->contextMock = $this->createPartialMock(
            \Magento\Backend\Block\Template\Context::class,
            ['getLayout', 'getEscaper', 'getUrlBuilder']
        );
        $this->escaperMock = $this->createMock(\Magento\Framework\Escaper::class);
        $this->layoutMock = $this->createPartialMock(\Magento\Framework\View\Layout::class, ['createBlock']);
        $this->urlBuilderMock = $this->createMock(\Magento\Framework\Url::class);
        $this->contextMock->expects($this->any())
            ->method('getLayout')
            ->will($this->returnValue($this->layoutMock));
        $this->contextMock->expects($this->any())
            ->method('getEscaper')
            ->will($this->returnValue($this->escaperMock));
        $this->contextMock->expects($this->any())
            ->method('getUrlBuilder')
            ->will($this->returnValue($this->urlBuilderMock));
        $this->rmaDataMock = $this->createMock(\Magento\Rma\Helper\Data::class);
        $this->itemFormFactoryMock = $this->createPartialMock(\Magento\Rma\Model\Item\FormFactory::class, ['create']);
        $this->itemFactoryMock = $this->createPartialMock(\Magento\Sales\Model\Order\ItemFactory::class, ['create']);

        $this->item = $objectManager->getObject(
            \Magento\Rma\Block\Adminhtml\Rma\Edit\Item::class,
            [
                'formFactory' => $this->formFactoryMock,
                'registry' => $this->coreRegistryMock,
                'context' => $this->contextMock,
                'rmaData' => $this->rmaDataMock,
                'itemFormFactory' => $this->itemFormFactoryMock,
                'itemFactory' => $this->itemFactoryMock,
            ]
        );
    }

    public function testInitForm()
    {
        $htmlPrefixId = 1;

        $item = $this->createMock(\Magento\Rma\Model\Item::class);

        $customerForm = $this->createPartialMock(
            \Magento\Rma\Model\Item\Form::class,
            ['setEntity', 'setFormCode', 'initDefaultValues', 'getUserAttributes']
        );
        $customerForm->expects($this->any())
            ->method('setEntity')
            ->will($this->returnSelf());
        $customerForm->expects($this->any())
            ->method('setFormCode')
            ->will($this->returnSelf());
        $customerForm->expects($this->any())
            ->method('initDefaultValues')
            ->will($this->returnSelf());
        $customerForm->expects($this->any())
            ->method('getUserAttributes')
            ->will($this->returnValue([]));

        $this->itemFormFactoryMock->expects($this->once())
            ->method('create')
            ->will($this->returnValue($customerForm));

        $this->coreRegistryMock->expects($this->any())
            ->method('registry')
            ->with('current_rma_item')
            ->will($this->returnValue($item));

        $fieldsetMock = $this->createMock(\Magento\Framework\Data\Form\Element\Fieldset::class);

        $formMock = $this->createPartialMock(
            \Magento\Framework\Data\Form::class,
            ['setHtmlIdPrefix', 'addFieldset', 'setValues', 'setParent', 'setBaseUrl']
        );
        $formMock->expects($this->once())
            ->method('setHtmlIdPrefix')
            ->with($htmlPrefixId . '_rma')
            ->will($this->returnValue($htmlPrefixId));
        $formMock->expects($this->any())
            ->method('addFieldset')
            ->will($this->returnValue($fieldsetMock));
        $this->formFactoryMock->expects($this->once())
            ->method('create')
            ->will($this->returnValue($formMock));

        $blockMock = $this->createMock(\Magento\Backend\Block\Widget\Button::class);
        $blockMock->expects($this->any())
            ->method('setData')
            ->will($this->returnSelf());

        $this->layoutMock->expects($this->any())
            ->method('createBlock')
            ->with(\Magento\Backend\Block\Widget\Button::class)
            ->will($this->returnValue($blockMock));
        $this->item->setHtmlPrefixId($htmlPrefixId);
        $result = $this->item->initForm();
        $this->assertInstanceOf(\Magento\Rma\Block\Adminhtml\Rma\Edit\Item::class, $result);
    }
}
