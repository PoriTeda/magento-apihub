<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Test\Unit\Model\Action;

class AdminTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Reward\Model\Action\Admin
     */
    protected $model;

    protected function setUp()
    {
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->model = $objectManagerHelper->getObject(\Magento\Reward\Model\Action\Admin::class);
    }

    public function testCanAddRewardPoints()
    {
        $this->assertTrue($this->model->canAddRewardPoints());
    }

    public function testGetHistoryMessage()
    {
        $this->assertEquals('Updated by moderator', $this->model->getHistoryMessage());
    }
}
