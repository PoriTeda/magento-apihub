<?php
/**
 * TmpRma
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\TmpRma
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\TmpRma\Model\Rma;

use Magento\Framework\Model\AbstractModel;

/**
 * Class Item
 *
 * @category  RIKI
 * @package   Riki\TmpRma\Model
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Item extends AbstractModel
{
    /**
     * {@inheritdoc}
     *
     * @return void
     */
    protected function _construct() //@codingStandardsIgnoreLine
    {
        $this->_init('Riki\TmpRma\Model\ResourceModel\Rma\Item');
    }
}
