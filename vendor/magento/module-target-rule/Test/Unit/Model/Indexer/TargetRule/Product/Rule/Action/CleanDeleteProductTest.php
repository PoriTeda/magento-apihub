<?php
/**
 * @category    Magento
 * @package     Magento_TargetRule
 * @subpackage  unit_tests
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\TargetRule\Test\Unit\Model\Indexer\TargetRule\Product\Rule\Action;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class CleanDeleteProductTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Action\CleanDeleteProduct
     */
    protected $_model;

    protected function setUp()
    {
        $objectManager = new ObjectManager($this);
        $this->_model = $objectManager->getObject(
            \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Action\CleanDeleteProduct::class
        );
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage We can't rebuild the index for an undefined product.
     */
    public function testEmptyIds()
    {
        $this->_model->execute(null);
    }

    public function testCleanDeleteProduct()
    {
        $ruleFactoryMock = $this->createPartialMock(\Magento\TargetRule\Model\RuleFactory::class, ['create']);

        $collectionFactoryMock = $this->createPartialMock(
            \Magento\TargetRule\Model\ResourceModel\Rule\CollectionFactory::class,
            ['create']
        );

        $resourceMock = $this->createMock(\Magento\TargetRule\Model\ResourceModel\Index::class);

        $resourceMock->expects($this->once())
            ->method('deleteProductFromIndex')
            ->will($this->returnValue(1));

        $storeManagerMock = $this->getMockForAbstractClass(\Magento\Store\Model\StoreManagerInterface::class);
        $timezoneMock = $this->getMockForAbstractClass(\Magento\Framework\Stdlib\DateTime\TimezoneInterface::class);

        $model = new \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Action\CleanDeleteProduct(
            $ruleFactoryMock,
            $collectionFactoryMock,
            $resourceMock,
            $storeManagerMock,
            $timezoneMock
        );

        $model->execute(2);
    }
}
