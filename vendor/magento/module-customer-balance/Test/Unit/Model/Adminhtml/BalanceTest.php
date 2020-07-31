<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Test\Unit\Model\Adminhtml;

use Magento\CustomerBalance\Model\Adminhtml\Balance;

/**
 * Test \Magento\CustomerBalance\Model\Adminhtml\Balance
 */
class BalanceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Balance
     */
    protected $_model;

    protected function setUp()
    {
        $helper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        /** @var Balance $model */
        $this->_model = $helper->getObject(\Magento\CustomerBalance\Model\Adminhtml\Balance::class);
    }

    public function testGetWebsiteIdWithException()
    {
        $this->expectException(\Magento\Framework\Exception\LocalizedException::class);
        $this->expectExceptionMessage((string)__('Please set a website ID.'));
        $this->_model->getWebsiteId();
    }

    public function testGetWebsiteId()
    {
        $this->_model->setWebsiteId('some id');
        $this->assertEquals('some id', $this->_model->getWebsiteId());
    }
}
