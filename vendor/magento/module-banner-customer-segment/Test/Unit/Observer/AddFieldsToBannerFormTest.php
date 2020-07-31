<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\BannerCustomerSegment\Test\Unit\Observer;

use Magento\BannerCustomerSegment\Observer\AddFieldsToBannerForm;

class AddFieldsToBannerFormTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Magento\BannerCustomerSegment\Observer\AddFieldsToBannerForm
     */
    private $addFieldsToBannerFormObserver;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $_segmentHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $_formKeyMock;

    protected function setUp()
    {
        $this->_segmentHelper = $this->createPartialMock(
            \Magento\CustomerSegment\Helper\Data::class,
            ['isEnabled', 'addSegmentFieldsToForm']
        );

        $this->addFieldsToBannerFormObserver = new AddFieldsToBannerForm(
            $this->_segmentHelper
        );

        $this->_formKeyMock = $this->createMock(\Magento\Framework\Data\Form\FormKey::class);
    }

    protected function tearDown()
    {
        $this->_segmentHelper = null;
        $this->addFieldsToBannerFormObserver = null;
        $this->_formKeyMock = null;
    }

    public function testAddFieldsToBannerForm()
    {
        $this->_segmentHelper->expects($this->any())->method('isEnabled')->will($this->returnValue(true));

        $factory = $this->createMock(\Magento\Framework\Data\Form\Element\Factory::class);
        $collectionFactory = $this->createPartialMock(
            \Magento\Framework\Data\Form\Element\CollectionFactory::class,
            ['create']
        );
        $form = new \Magento\Framework\Data\Form($factory, $collectionFactory, $this->_formKeyMock);
        $model = new \Magento\Framework\DataObject();
        $block = $this->createMock(\Magento\Backend\Block\Widget\Form\Element\Dependence::class);

        $this->_segmentHelper->expects($this->once())->method('addSegmentFieldsToForm')->with($form, $model, $block);

        $this->addFieldsToBannerFormObserver->execute(
            new \Magento\Framework\Event\Observer(
                [
                    'event' => new \Magento\Framework\DataObject(
                        ['form' => $form, 'model' => $model, 'after_form_block' => $block]
                    ),
                ]
            )
        );
    }

    public function testAddFieldsToBannerFormDisabled()
    {
        $this->_segmentHelper->expects($this->any())->method('isEnabled')->will($this->returnValue(false));

        $factory = $this->createMock(\Magento\Framework\Data\Form\Element\Factory::class);
        $collectionFactory = $this->createPartialMock(
            \Magento\Framework\Data\Form\Element\CollectionFactory::class,
            ['create']
        );

        $form = new \Magento\Framework\Data\Form($factory, $collectionFactory, $this->_formKeyMock);
        $model = new \Magento\Framework\DataObject();
        $block = $this->createMock(\Magento\Backend\Block\Widget\Form\Element\Dependence::class);

        $this->_segmentHelper->expects($this->never())->method('addSegmentFieldsToForm');

        $this->addFieldsToBannerFormObserver->execute(
            new \Magento\Framework\Event\Observer(
                [
                    'event' => new \Magento\Framework\DataObject(
                        ['form' => $form, 'model' => $model, 'after_form_block' => $block]
                    ),
                ]
            )
        );
    }
}
