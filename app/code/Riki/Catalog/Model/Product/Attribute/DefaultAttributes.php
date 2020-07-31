<?php
/**
 * Catalog.
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Catalog
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\Catalog\Model\Product\Attribute;

/**
 * DefaultAttributes.
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Catalog
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class DefaultAttributes extends \Magento\Catalog\Model\Product\Attribute\DefaultAttributes
{
    /**
     * Retrieve default entity static attributes
     *
     * @return string[]
     */
    public function getDefaultAttributes()
    {
        return ['entity_id', 'attribute_set_id', 'type_id', 'created_at', 'updated_at', 'sku', 'flag_export_bi'];
    }
}
