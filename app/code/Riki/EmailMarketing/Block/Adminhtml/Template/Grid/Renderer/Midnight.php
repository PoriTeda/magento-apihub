<?php
/**
 * Email Marketing
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\EmailMarketing\Block\Adminhtml\Template\Grid\Renderer
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\EmailMarketing\Block\Adminhtml\Template\Grid\Renderer;

/**
 * Class Midnight
 *
 * @category  RIKI
 * @package   Riki\EmailMarketing\Block\Adminhtml\Template\Grid\Renderer
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Midnight extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * Email template types
     *
     * @var array
     */
    protected static $_types = [
        0 => 'No',
        1 => 'Yes',
    ];

    /**
     * Render grid column
     *
     * @param \Magento\Framework\DataObject $row
     * @return \Magento\Framework\Phrase
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $str = __('Yes');

        if (isset(self::$_types[$row->getSendMidnight()])) {
            $str = self::$_types[$row->getSendMidnight()];
        }

        return __($str);
    }
}
