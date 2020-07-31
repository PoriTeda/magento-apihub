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

use Wyomind\PointOfSale\Model\PointOfSaleFactory;

/**
 * Class ReturnedWarehouse
 *
 * @category  RIKI
 * @package   Riki\TmpRma\Model\Config
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class ReturnedWarehouse extends \Riki\Framework\Model\Source\AbstractOption
{
    protected $pointOfSaleFactory;
    protected $functionCache;

    /**
     * ReturnedWarehouse constructor.
     *
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache      helper
     * @param PointOfSaleFactory                         $pointOfSaleFactory factory
     */
    public function __construct(
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Wyomind\PointOfSale\Model\PointOfSaleFactory $pointOfSaleFactory
    ) {
        $this->functionCache = $functionCache;
        $this->pointOfSaleFactory = $pointOfSaleFactory;
    }

    /**
     * Get warehouse collection
     *
     * @return array
     */
    public function getWarehouses()
    {
        if ($this->functionCache->has()) {
            return $this->functionCache->load();
        }
        $collection = $this->pointOfSaleFactory->create()->getCollection();
        $collection->setOrder('position', $collection::SORT_ORDER_ASC);
        $result = $collection->getItems();

        $this->functionCache->store($result);

        return $result;
    }


    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function prepare()
    {
        $options = [];

        foreach ($this->getWarehouses() as $warehouse) {
            $options[$warehouse->getId()] = $warehouse->getName();
        }

        return $options;
    }

    /**
     * Get warehouse options array [value => label]
     *
     * @param string $field
     * @return array
     */
    public function getSAPCodes($field = 'place_id')
    {
        $options = [];
        switch ($field) {
            case 'store_code':
                $identifier =  'getStoreCode';
                break;
            default:
                $identifier =  'getId';
                break;
        }
        /** @var \Wyomind\PointOfSale\Model\PointOfSale $warehouse */
        foreach ($this->getWarehouses() as $warehouse) {
            $options[$warehouse->{$identifier}()] = $warehouse->getData('sap_code');
        }
        return $options;
    }
}

