<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\AdminGws\Test\Unit\Model;

class BlocksTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\AdminGws\Model\Blocks
     */
    protected $_model;

    protected function setUp()
    {
        $helper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->_model = $helper->getObject(\Magento\AdminGws\Model\Blocks::class);
    }

    public function testDisableTaxRelatedMultiselects()
    {
        $form = $this->createPartialMock(\Magento\Framework\Data\Form::class, ['getElement', 'setDisabled']);
        $form->expects(
            $this->exactly(3)
        )->method(
            'getElement'
        )->with(
            $this->logicalOr(
                $this->equalTo('tax_customer_class'),
                $this->equalTo('tax_product_class'),
                $this->equalTo('tax_rate')
            )
        )->will(
            $this->returnSelf()
        );

        $form->expects(
            $this->exactly(3)
        )->method(
            'setDisabled'
        )->with(
            $this->equalTo(true)
        )->will(
            $this->returnSelf()
        );

        $observerMock = new \Magento\Framework\DataObject(
            [
                'event' => new \Magento\Framework\DataObject(
                    [
                        'block' => new \Magento\Framework\DataObject(['form' => $form]),
                    ]
                ),
            ]
        );

        $this->_model->disableTaxRelatedMultiselects($observerMock);
    }
}
