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
namespace Riki\TmpRma\Model\ResourceModel\Rma\Item;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Class Collection
 *
 * @category  RIKI
 * @package   Riki\TmpRma\Model
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Collection extends AbstractCollection
{
    /**
     * {@inheritdoc}
     *
     * @return void
     */
    protected function _construct() //@codingStandardsIgnoreLine
    {
        $this->_init(
            'Riki\TmpRma\Model\Rma\Item',
            'Riki\TmpRma\Model\ResourceModel\Rma\Item'
        );
    }

    /**
     * Add rma to filter
     *
     * @param \Riki\TmpRma\Model\Rma $rma rma
     *
     * @return $this
     */
    public function setRmaFilter(\Riki\TmpRma\Model\Rma $rma)
    {
        $this->addFieldToFilter('parent_id', $rma->getId());

        return $this;
    }
}
