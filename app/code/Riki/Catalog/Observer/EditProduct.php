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

namespace Riki\Catalog\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * EditProduct
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\CurrencySymbol
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class EditProduct implements ObserverInterface
{
    /**
     * Session
     *
     * @var \Magento\Backend\Model\Session
     */
    protected $session;

    /**
     * RegisterVisitObserver constructor.
     *
     * @param \Magento\Backend\Model\Session $session Session
     */
    public function __construct(
        \Magento\Backend\Model\Session $session
    ) {
        $this->session = $session;
    }

    /**
     * Execute
     *
     * @param \Magento\Framework\Event\Observer $observer Observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $this->session->unsProductData();
    }
}
