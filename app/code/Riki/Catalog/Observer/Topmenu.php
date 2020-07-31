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

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Data\Tree\Node;
use Magento\Framework\Event\ObserverInterface;

/**
 * Topmenu.
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
class Topmenu implements ObserverInterface
{

    /**
     * Execute
     *
     * @param EventObserver $observer EventObserver
     *
     * @return $this
     */
    public function execute(EventObserver $observer)
    {
        /**
         * Node
         *
         * @var \Magento\Framework\Data\Tree\Node $menu Node
         */
        $menu = $observer->getMenu();
        $tree = $menu->getTree();
        $data = [
            'name'      => __('Multiple Product List'),
            'id'        => 'list-multiple-product',
            'url'       => '/catalog/multiple/index',
            'is_active' => (1)
        ];
        $node = new Node($data, 'id', $tree, $menu);
        $menu->addChild($node);
        return $this;
    }
}