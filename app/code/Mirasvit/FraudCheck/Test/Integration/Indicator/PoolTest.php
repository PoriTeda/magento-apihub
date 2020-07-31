<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-fraud-check
 * @version   1.0.6
 * @copyright Copyright (C) 2016 Mirasvit (https://mirasvit.com/)
 */



namespace Mirasvit\FraudCheck\Indicator;

use Magento\TestFramework\Helper\Bootstrap;

class PoolTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var \Mirasvit\FraudCheck\Rule\Pool
     */
    protected $pool;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();

        $this->pool = $this->objectManager->create('Mirasvit\FraudCheck\Rule\Pool');
    }


    public function testGetRules()
    {
        foreach ($this->pool->getRules() as $rule) {
            echo $rule->getFraudScore();
        }
    }
}
