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
namespace Riki\TmpRma\Model;

use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;


/**
 * Class Rma
 *
 * @category  RIKI
 * @package   Riki\TmpRma\Model
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Rma extends \Magento\Framework\Model\AbstractModel
{
    /**
     * RmaItemCollectionFactory
     *
     * @var ResourceModel\Rma\Item\CollectionFactory
     */
    protected $rmaItemFactory;

    /**
     * Rma constructor.
     *
     * @param \Magento\Framework\Model\Context $context            context
     * @param \Magento\Framework\Registry      $registry           registry
     * @param Rma\ItemFactory                  $rmaItemFactory     factory
     * @param AbstractResource|null            $resource           resource
     * @param AbstractDb|null                  $resourceCollection collection
     * @param array                            $data               param
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Riki\TmpRma\Model\Rma\ItemFactory $rmaItemFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
        $this->rmaItemFactory = $rmaItemFactory;
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    protected function _construct() // @codingStandardsIgnoreLine
    {
        $this->_init('Riki\TmpRma\Model\ResourceModel\Rma');
    }

    /**
     * Get item collection
     *
     * @return AbstractCollection
     */
    public function getItemsCollection()
    {
        $collection = $this->rmaItemFactory
            ->create()
            ->getCollection()
            ->setRmaFilter($this);

        if ($this->getId()) {
            foreach ($collection as $item) {
                $item->setRma($this);
            }
        }
        return $collection;
    }
}
