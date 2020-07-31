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


namespace Mirasvit\FraudCheck\Indicator\Weird;

use Magento\TestFramework\Helper\Bootstrap;

class PhoneTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var \Mirasvit\FraudCheck\Rule\Weird\Phone
     */
    protected $phone;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->phone = $this->objectManager->create('Mirasvit\FraudCheck\Rule\Weird\Phone');
    }

    /**
     * @param string     $phone
     * @param bool|float $score
     * @dataProvider dataProvider
     */
    public function testGetFraudScore($phone, $score)
    {
        $this->phone->getContext()->setData(['billing_phone' => $phone]);

        $result = $this->phone->getFraudScore();
        echo $result . ' - ' . $phone . $score . PHP_EOL;
    }

    /**
     * @return array
     */
    public function dataProvider()
    {
        return [
            ['0937622535', 0],
            ['09322211122', 25],
            ['093222290', 75],
            ['123123123', 50],
            ['0012121200', 25],
            ['11111111', 100],
            ['999992', 75]
        ];
    }
}
