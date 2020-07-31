<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Test\Unit\Helper;

class DataTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CustomerSegment\Helper\Data
     */
    private $_helper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $_scopeConfig;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $_segmentCollection;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $_formKeyMock;

    protected function setUp()
    {
        $this->_formKeyMock = $this->createMock(\Magento\Framework\Data\Form\FormKey::class);

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $className = \Magento\CustomerSegment\Helper\Data::class;
        $arguments = $objectManager->getConstructArguments($className);
        /** @var \Magento\Framework\App\Helper\Context $context */
        $context = $arguments['context'];

        $this->_scopeConfig = $context->getScopeConfig();
        $this->_segmentCollection = $arguments['segmentCollection'];

        $this->_helper = $objectManager->getObject($className, $arguments);
    }

    protected function tearDown()
    {
        $this->_helper = null;
        $this->_scopeConfig = null;
        $this->_segmentCollection = null;
    }

    /**
     * @param array $fixtureFormData
     * @dataProvider addSegmentFieldsToFormDataProvider
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testAddSegmentFieldsToForm(array $fixtureFormData)
    {
        $this->_scopeConfig->expects(
            $this->once()
        )->method(
            'getValue'
        )->with(
            \Magento\CustomerSegment\Helper\Data::XML_PATH_CUSTOMER_SEGMENT_ENABLER
        )->will(
            $this->returnValue('1')
        );

        $this->_segmentCollection->expects(
            $this->once()
        )->method(
            'toOptionArray'
        )->will(
            $this->returnValue([10 => 'Devs', 20 => 'QAs'])
        );

        $fieldset = $this->createPartialMock(\Magento\Framework\Data\Form\Element\Fieldset::class, ['addField']);
        $fieldset->expects(
            $this->at(0)
        )->method(
            'addField'
        )->with(
            $this->logicalOr($this->equalTo('use_customer_segment'), $this->equalTo('select'))
        );
        $fieldset->expects(
            $this->at(1)
        )->method(
            'addField'
        )->with(
            $this->logicalOr($this->equalTo('customer_segment_ids'), $this->equalTo('multiselect'))
        );

        $form = $this->createPartialMock(\Magento\Framework\Data\Form::class, ['getElement', 'getHtmlIdPrefix']);
        $form->expects(
            $this->once()
        )->method(
            'getElement'
        )->with(
            $this->equalTo('base_fieldset')
        )->will(
            $this->returnValue($fieldset)
        );
        $form->expects($this->once())->method('getHtmlIdPrefix')->will($this->returnValue('pfx_'));

        $data = new \Magento\Framework\DataObject($fixtureFormData);

        $dependencies = $this->createPartialMock(
            \Magento\Backend\Block\Widget\Form\Element\Dependence::class,
            ['addFieldMap', 'addFieldDependence']
        );
        $dependencies->expects(
            $this->at(0)
        )->method(
            'addFieldMap'
        )->with(
            'pfx_use_customer_segment',
            'use_customer_segment'
        )->will(
            $this->returnSelf()
        );
        $dependencies->expects(
            $this->at(1)
        )->method(
            'addFieldMap'
        )->with(
            'pfx_customer_segment_ids',
            'customer_segment_ids'
        )->will(
            $this->returnSelf()
        );
        $dependencies->expects(
            $this->once()
        )->method(
            'addFieldDependence'
        )->with(
            'customer_segment_ids',
            'use_customer_segment',
            '1'
        )->will(
            $this->returnSelf()
        );

        $this->_helper->addSegmentFieldsToForm($form, $data, $dependencies);
    }

    public function addSegmentFieldsToFormDataProvider()
    {
        return [
            'all segments' => [[]],
            'specific segments' => [['customer_segment_ids' => [123, 456]]]
        ];
    }

    public function testAddSegmentFieldsToFormDisabled()
    {
        $this->_scopeConfig->expects(
            $this->once()
        )->method(
            'getValue'
        )->with(
            \Magento\CustomerSegment\Helper\Data::XML_PATH_CUSTOMER_SEGMENT_ENABLER
        )->will(
            $this->returnValue('0')
        );

        $this->_segmentCollection->expects($this->never())->method('toOptionArray');

        $factory = $this->createMock(\Magento\Framework\Data\Form\Element\Factory::class);
        $collectionFactory = $this->createPartialMock(
            \Magento\Framework\Data\Form\Element\CollectionFactory::class,
            ['create']
        );
        $form = new \Magento\Framework\Data\Form(
            $factory,
            $collectionFactory,
            $this->_formKeyMock,
            ['html_id_prefix' => 'pfx_']
        );
        $data = new \Magento\Framework\DataObject();
        $dependencies = $this->createPartialMock(
            \Magento\Backend\Block\Widget\Form\Element\Dependence::class,
            ['addFieldMap', 'addFieldDependence']
        );

        $dependencies->expects($this->never())->method('addFieldMap');
        $dependencies->expects($this->never())->method('addFieldDependence');

        $this->_helper->addSegmentFieldsToForm($form, $data, $dependencies);

        $this->assertNull($data->getData('use_customer_segment'));
        $this->assertNull($form->getElement('use_customer_segment'));
        $this->assertNull($form->getElement('customer_segment_ids'));
    }
}
