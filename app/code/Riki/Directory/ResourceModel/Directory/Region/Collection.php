<?php
/**
 * Directory
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Directory
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\Directory\ResourceModel\Directory\Region;

/**
 * Collection
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Directory
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Collection extends \Magento\Directory\Model\ResourceModel\Region\Collection
{
    /**
     * Define main, country, locale region name tables
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();

        if ($this->_localeResolver->getLocale() == 'ja_JP') {
            unset($this->_orders['name']);
            unset($this->_orders['default_name']);
        }
    }

    /**
     * Convert collection items to select options array
     *
     * @return array
     */
    public function toOptionArray()
    {
        if ($this->_localeResolver->getLocale() != 'ja_JP') {
            $options = $this->_toOptionArray(
                'region_id',
                'default_name',
                ['title' => 'default_name', 'country_id' => 'country_id']
            );
        } else {
            $options = $this->_toOptionArray(
                'region_id',
                'name',
                ['title' => 'name', 'country_id' => 'country_id']
            );
        }

        if (count($options) > 0) {
            array_unshift(
                $options,
                ['title ' => null,
                    'value' => null,
                    'label' => __('-- Please select --')
                ]
            );
        }
        return $options;
    }
}

