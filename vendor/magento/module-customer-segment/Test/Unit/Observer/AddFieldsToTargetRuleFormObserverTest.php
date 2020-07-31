<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomerSegment\Test\Unit\Observer;

class AddFieldsToTargetRuleFormObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CustomerSegment\Observer\AddFieldsToTargetRuleFormObserver
     */
    private $_model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $_segmentHelper;

    protected function setUp()
    {
        $this->_segmentHelper = $this->createPartialMock(
            \Magento\CustomerSegment\Helper\Data::class,
            ['isEnabled', 'addSegmentFieldsToForm']
        );

        $this->_model = new \Magento\CustomerSegment\Observer\AddFieldsToTargetRuleFormObserver(
            $this->_segmentHelper
        );
    }

    protected function tearDown()
    {
        $this->_model = null;
        $this->_segmentHelper = null;
    }

    public function testAddFieldsToTargetRuleForm()
    {
        $this->_segmentHelper->expects($this->any())->method('isEnabled')->will($this->returnValue(true));

        $formDependency = $this->createMock(\Magento\Backend\Block\Widget\Form\Element\Dependence::class);

        $layout = $this->createPartialMock(\Magento\Framework\View\Layout::class, ['createBlock']);
        $layout->expects(
            $this->once()
        )->method(
            'createBlock'
        )->with(
            \Magento\Backend\Block\Widget\Form\Element\Dependence::class
        )->will(
            $this->returnValue($formDependency)
        );

        $factoryElement = $this->createMock(\Magento\Framework\Data\Form\Element\Factory::class);
        $collectionFactory = $this->createPartialMock(
            \Magento\Framework\Data\Form\Element\CollectionFactory::class,
            ['create']
        );
        $formKey = $this->createMock(\Magento\Framework\Data\Form\FormKey::class);
        $form = new \Magento\Framework\Data\Form($factoryElement, $collectionFactory, $formKey);
        $model = new \Magento\Framework\DataObject();
        $block = new \Magento\Framework\DataObject(['layout' => $layout]);

        $this->_segmentHelper->expects(
            $this->once()
        )->method(
            'addSegmentFieldsToForm'
        )->with(
            $form,
            $model,
            $formDependency
        );

        $this->_model->execute(
            new \Magento\Framework\Event\Observer(
                [
                    'event' => new \Magento\Framework\DataObject(
                        ['form' => $form, 'model' => $model, 'block' => $block]
                    )
                ]
            )
        );
    }

    public function testAddFieldsToTargetRuleFormDisabled()
    {
        $this->_segmentHelper->expects($this->any())->method('isEnabled')->will($this->returnValue(false));

        $layout = $this->createPartialMock(\Magento\Framework\View\Layout::class, ['createBlock']);
        $layout->expects($this->never())->method('createBlock');

        $factoryElement = $this->createMock(\Magento\Framework\Data\Form\Element\Factory::class);
        $collectionFactory = $this->createPartialMock(
            \Magento\Framework\Data\Form\Element\CollectionFactory::class,
            ['create']
        );
        $formKey = $this->createMock(\Magento\Framework\Data\Form\FormKey::class);
        $form = new \Magento\Framework\Data\Form($factoryElement, $collectionFactory, $formKey);
        $model = new \Magento\Framework\DataObject();
        $block = new \Magento\Framework\DataObject(['layout' => $layout]);

        $this->_segmentHelper->expects($this->never())->method('addSegmentFieldsToForm');

        $this->_model->execute(
            new \Magento\Framework\Event\Observer(
                [
                    'event' => new \Magento\Framework\DataObject(
                        ['form' => $form, 'model' => $model, 'block' => $block]
                    )
                ]
            )
        );
    }
}
