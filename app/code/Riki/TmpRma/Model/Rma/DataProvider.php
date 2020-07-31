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

use Riki\TmpRma\Model\Rma;
use Riki\TmpRma\Model\ResourceModel\Rma\Collection;
use Riki\TmpRma\Model\ResourceModel\Rma\CollectionFactory as RmaCollectionFactory;

/**
 * Class DataProvider
 *
 * @category  RIKI
 * @package   Riki\TmpRma\Model
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * Collection
     *
     * @var Collection
     */
    protected $collection;

    /**
     * LoadedData
     *
     * @var array
     */
    protected $loadedData;

    /**
     * DataProvider constructor.
     *
     * @param string               $name                 param
     * @param string               $primaryFieldName     param
     * @param string               $requestFieldName     param
     * @param RmaCollectionFactory $rmaCollectionFactory factory
     * @param array                $meta                 param
     * @param array                $data                 param
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        RmaCollectionFactory $rmaCollectionFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $meta,
            $data
        );
        $this->collection = $rmaCollectionFactory->create();
        $this->collection->addFieldToSelect('*');
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $items = $this->collection->getItems();
        /**
         * Type hinting
         *
         * @var Rma $rma
         */
        foreach ($items as $rma) {
            $result['rma'] = $rma->getData();
            unset($result['items']);

            /**
             * Type hinting
             *
             * @var Item $item
             */
            foreach ($rma->getItemsCollection() as $item) {
                $itemId = $item->getId();
                $item->load($itemId);
                $result['items'][$itemId] = $item->getData();
            }
            $this->loadedData[$rma->getId()] = $result;
        }

        return $this->loadedData;
    }
}
