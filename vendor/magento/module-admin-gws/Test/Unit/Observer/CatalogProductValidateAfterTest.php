<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdminGws\Test\Unit\Observer;

class CatalogProductValidateAfterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\AdminGws\Observer\CatalogProductValidateAfter
     */
    private $catalogProductValidateAfterObserver;

    /**
     * @var \Magento\Framework\Event\Observer
     */
    private $observer;

    /**
     * @var \Magento\AdminGws\Model\Models
     */
    private $models;

    /**
     * @return void
     */
    protected function setUp()
    {
        $this->models = $this->getMockBuilder(
            \Magento\AdminGws\Model\Models::class
        )->setMethods(
            ['catalogProductValidateAfter']
        )->disableOriginalConstructor()->getMock();

        $this->observer = $this->getMockBuilder(
            \Magento\Framework\Event\Observer::class
        )->disableOriginalConstructor()->getMock();

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->catalogProductValidateAfterObserver = $objectManagerHelper->getObject(
            \Magento\AdminGws\Observer\CatalogProductValidateAfter::class,
            [
                'models' => $this->models,
            ]
        );
    }

    /**
     * @return void
     */
    public function testUpdateRoleStores()
    {
        $this->models->expects($this->atLeastOnce())->method('catalogProductValidateAfter');
        $this->catalogProductValidateAfterObserver->execute($this->observer);
    }
}
