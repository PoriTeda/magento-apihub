<?php
/**
 * CvsPayment
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\CvsPayment
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\CvsPayment\Model\ResourceModel\Order\Grid;

use \Magento\Framework\Data;
use \Magento\Framework\App;
use \Magento\Framework\Event;
use Riki\CvsPayment\Api\ConstantInterface;
use Riki\Sales\Model\ResourceModel\Sales\Grid\OrderStatus;

/**
 * Class Collection
 *
 * @category  RIKI
 * @package   Riki\CvsPayment\Model
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Collection
    extends \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult
{
    /**
     * Collection constructor.
     *
     * @param App\RequestInterface                      $request       api
     * @param Data\Collection\EntityFactoryInterface    $entityFactory factory
     * @param \Psr\Log\LoggerInterface                  $logger        logger
     * @param Data\Collection\Db\FetchStrategyInterface $fetchStrategy helper
     * @param Event\ManagerInterface                    $eventManager  helper
     * @param string                                    $mainTable     string
     * @param string                                    $resourceModel string
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        $mainTable = 'sales_order_grid',
        $resourceModel = 'Magento\Sales\Model\ResourceModel\Order'
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $mainTable,
            $resourceModel
        );

        $status = $request->getParam('status');
        if ($status == ConstantInterface::REGISTRY_PENDING_CVS_30_DAYS) {
            $this->addFieldToFilter(
                'status',
                [
                    'eq' => OrderStatus::STATUS_ORDER_PENDING_CVS
                ]
            );
            $this->addFieldToFilter(
                'csv_start_date',
                [
                    'lt' => new \Zend_Db_Expr('NOW() - INTERVAL 30 DAY')
                ]
            );
        }
    }
}
