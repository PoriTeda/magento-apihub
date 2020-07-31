<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogEvent\Test\Unit\Model\ResourceModel\Event\Grid;

use Magento\CatalogEvent\Model\ResourceModel\Event\Grid\State;
use Magento\Framework\Phrase;

/**
 * Unit test for
 */
class StateTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CatalogEvent\Model\ResourceModel\Event\Grid\State
     */
    protected $state;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->state = new State();
    }

    /**
     * @return void
     */
    public function testToOptionArray()
    {
        foreach ($this->state->toOptionArray() as $item) {
            $this->assertTrue($item instanceof Phrase || is_string($item));
        }
    }
}
