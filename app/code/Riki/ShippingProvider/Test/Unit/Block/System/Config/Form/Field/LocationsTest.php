<?php
/**
 * ShippingProvider
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\ShippingProvider
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\ShippingProvider\Test\Unit\Block\System\Config\Form\Field;

use Riki\ShippingProvider\Block\System\Config\Form\Field\Locations;

/**
 * LocationsTest
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\ShippingProvider
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class LocationsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Locations
     *
     * @var Locations
     */
    protected $block;

    /**
     * SetUp
     *
     * @inheritdoc
     *
     * @return $this
     */
    protected function setUp()
    {
        /* @var \Magento\Backend\Block\Template\Context $context */
        $context = $this->getMockBuilder('Magento\Backend\Block\Template\Context')
            ->disableOriginalConstructor()
            ->getMock();

        $this->block = new Locations($context);
    }

    /**
     * TestGetColumns
     *
     * @return $this
     */
    public function testGetColumns()
    {
        $this->assertArrayHasKey('title', $this->block->getColumns());
        $this->assertArrayHasKey('street', $this->block->getColumns());
        $this->assertArrayHasKey('phone', $this->block->getColumns());
        $this->assertArrayHasKey('message', $this->block->getColumns());
        $this->assertCount(4, $this->block->getColumns());
    }
}
