<?php
/**
 * PHP version 7
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 *
 * @category  Riki_EmailMarketing
 * @package   Riki\EmailMarketing\Model
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\EmailMarketing\Model;
use Magento\Framework\Data\OptionSourceInterface;
/**
 * Class Hours
 *
 * @category  Riki_EmailMarketing
 * @package   Riki\EmailMarketing\Model
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Hours implements OptionSourceInterface
{
    /**
     * {@inheritDoc}
     */
    public function toOptionArray()
    {
        return [
            ['value' => '00', 'label' => '00'],
            ['value' => '01', 'label' => '01'],
            ['value' => '02', 'label' => '02'],
            ['value' => '03', 'label' => '03'],
            ['value' => '04', 'label' => '04'],
            ['value' => '05', 'label' => '05'],
            ['value' => '06', 'label' => '06'],
            ['value' => '07', 'label' => '07'],
            ['value' => '08', 'label' => '08'],
            ['value' => '09', 'label' => '09'],
            ['value' => '10', 'label' => '10'],
            ['value' => '11', 'label' => '11'],
            ['value' => '12', 'label' => '12'],
            ['value' => '13', 'label' => '13'],
            ['value' => '14', 'label' => '14'],
            ['value' => '15', 'label' => '15'],
            ['value' => '16', 'label' => '16'],
            ['value' => '17', 'label' => '17'],
            ['value' => '18', 'label' => '18'],
            ['value' => '19', 'label' => '19'],
            ['value' => '20', 'label' => '20'],
            ['value' => '21', 'label' => '21'],
            ['value' => '22', 'label' => '22'],
            ['value' => '23', 'label' => '23']
        ];
    }

}
