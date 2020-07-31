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
namespace Riki\TmpRma\Model\Config\Source\Rma;

/**
 * Class Unit
 *
 * @category  RIKI
 * @package   Riki\TmpRma\Model\Config
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Unit extends \Riki\Framework\Model\Source\AbstractOption
{
    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function prepare()
    {
        return [
            0 => __('Case'),
            1 => __('Piece')
        ];
    }
}
