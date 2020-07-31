<?php
/**
 * Riki Product Active
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\ProductActive\Cron
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\ProductActive\Cron;

use Magento\Catalog\Model\Product\Attribute\Source\Status ;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Riki\ProductActive\Helper\Data as HelperData;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Cache\Frontend\Pool;
/**
 * Class ActiveProduct
 *
 * @category  RIKI
 * @package   Riki\ProductActive\Cron
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class ActiveProduct
{
    /**
     * @var \Magento\Catalog\Model\Product\Attribute\Source\Status
     */
    protected $_productStatus;
    /**
     * @var
     */
    protected $_productCollectionFactory;
    /**
     * @var ProductRepository
     */
    protected  $_productRepository;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_dateTime;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Riki\ProductActive\Helper\Data
     */
    protected $_dataHelper;
    /**
     * @var SearchCriteriaBuilder
     */
    protected $_criteriaBuilder;
    /**
     * @var FilterBuilder
     */
    protected $_filterBuilder;
    /**
     * @var FilterGroupBuilder
     */
    protected $_filterGroupBuilder;
    /**
     * @var TypeListInterface
     */
    protected $_cacheTypeList;
    /**
     * @var Pool
     */
    protected $_cacheFrontendPool;

    /**
     * ActiveProduct constructor.
     * @param ProductRepository $productRepository
     * @param CollectionFactory $collectionFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param Status $productStatus
     * @param LoggerInterface $logger
     * @param StoreManagerInterface $storeManager
     * @param DateTime $dateTime
     * @param HelperData $dataHelper
     * @param TimezoneInterface $timezone
     * @param FilterBuilder $filterBuilder
     * @param FilterGroupBuilder $filterGroupBuilder
     * @param TypeListInterface $typeList
     * @param Pool $pool
     */
    public function __construct(
        ProductRepository $productRepository,
        CollectionFactory $collectionFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Status $productStatus,
        LoggerInterface $logger,
        StoreManagerInterface $storeManager,
        DateTime $dateTime,
        HelperData $dataHelper,
        TimezoneInterface $timezone,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        TypeListInterface $typeList,
        Pool $pool
    ) {
        $this->_productStatus = $productStatus;
        $this->_productRepository = $productRepository;
        $this->_productCollectionFactory = $collectionFactory;
        $this->_criteriaBuilder = $searchCriteriaBuilder;
        $this->_logger = $logger;
        $this->_dateTime = $dateTime;
        $this->_storeManager = $storeManager;
        $this->_dataHelper = $dataHelper;
        $this->_timezone = $timezone;
        $this->_logger->setTimezone(new \DateTimeZone($timezone->getConfigTimezone()));
        $this->_filterBuilder = $filterBuilder;
        $this->_filterGroupBuilder = $filterGroupBuilder;
        $this->_cacheTypeList = $typeList;
        $this->_cacheFrontendPool = $pool;
    }

    /**
     * Clean expired quotes (cron process)
     *
     * @return void
     */
    public function execute()
    {
        if(!$this->_dataHelper->isEnable()) {
            return;
        }
        $originDate =  $this->_timezone->formatDateTime($this->_dateTime->gmtDate(),2,2);
        $needDate = $this->_dateTime->gmtDate('d-m-Y H:i:s',$originDate);
        $compareDate = $this->_dateTime->gmtDate('Y-m-d',$originDate);

        $this->_logger->info('------------------------------');
        $this->_logger->info(__('Product Active cronjob running at: '). $needDate);
        $this->_logger->info('------------------------------');

        $statusEnable = Status::STATUS_ENABLED;
        $statusDisabled = Status::STATUS_DISABLED;

        $fieldLaunchFrom = 'launch_from';
        $fieldLaunchTo = 'launch_to';
        $today  =  $this->_dateTime->gmtDate('Y-m-d',$originDate);

        /* Enable products */
        $this->_enableProducts
        (
            $today,
            $fieldLaunchFrom,
            $fieldLaunchTo,
            $statusEnable,
            $statusDisabled
        );
        /* Disable products */
        $this->_disableProducts
        (
            $today,
            $fieldLaunchFrom,
            $fieldLaunchTo,
            $statusEnable,
            $statusDisabled
        );
        //Flush cache
        $types = [
            'eav',
            'full_page'
        ];
        foreach ($types as $type) {
            $this->_cacheTypeList->cleanType($type);
        }
    }

    /**
     * @param $today
     * @param $fieldLaunchFrom
     * @param $fieldLaunchTo
     * @param $statusEnable
     * @param $statusDisabled
     */
    private function _enableProducts
    (
        $today,
        $fieldLaunchFrom,
        $fieldLaunchTo,
        $statusEnable,
        $statusDisabled
    )
    {
        /*
         * Active products
         */
        /*1.  Launch date to = Launch date from  */
        $filter[1][] = [$fieldLaunchFrom,true,'null'];
        $filter[1][] = [$fieldLaunchTo,true,'null'];
        /*2. Current date to < Launch date to & Launch date from ="" */
        $filter[2][] = [$fieldLaunchFrom,true,'null'];
        $filter[2][] = [$fieldLaunchTo,$today,'gt'];
        /*3. Launch date from < Current date & Launch date to ="" */
        $filter[3][] = [$fieldLaunchFrom,$today,'lt'];
        $filter[3][] = [$fieldLaunchTo,true,'null'];
        /*4. Launch date from < Current date < Launch date to */
        $filter[4][] = [$fieldLaunchFrom,$today,'lt'];
        $filter[4][] = [$fieldLaunchTo,$today,'gt'];
        /*4. Launch date from = Current date */
        $filter[5][] = [$fieldLaunchFrom,$today,'eq'];
        $filter[5][] = [$fieldLaunchFrom,$fieldLaunchTo,'lt'];
        for($i=1;$i<=5;$i++)
        {
            $criteria = $this->_criteriaBuilder
                        ->addFilter( 'status',$statusDisabled,'eq')
                        ->addFilter( $filter[$i][0][0],$filter[$i][0][1],$filter[$i][0][2] )
                        ->addFilter( $filter[$i][1][0],$filter[$i][1][1],$filter[$i][1][2] )
                        ->create();
            $producstCollection = $this->_productRepository->getList($criteria);
            if($producstCollection->getItems())
            {
                foreach($producstCollection->getItems() as $_product)
                {
                    try{
                        $this->_logger->info('Update status to enable products SKU #'.$_product->getSku());
                        $_product->setStatus($statusEnable)->save();
                    }catch(\Exception $e){
                        $this->_logger->critical($e->getMessage());
                    }
                }
            }
        }

    }//end function

    /**
     * @param $today
     * @param $fieldLaunchFrom
     * @param $fieldLaunchTo
     * @param $statusEnable
     * @param $statusDisabled
     */
    private function _disableProducts
    (
        $today,
        $fieldLaunchFrom,
        $fieldLaunchTo,
        $statusEnable,
        $statusDisabled
    )
    {
        /*
         * Disable  products
         */
        $filter[1][] = [$fieldLaunchFrom, $today, 'gt'];
        $filter[1][] = [$fieldLaunchTo, false, 'null'];
        $filter[2][] = [$fieldLaunchFrom, true, 'null'];
        $filter[2][] = [$fieldLaunchTo, $today, 'lt'];
        $filter[3][] = [$fieldLaunchFrom, $today, 'gt'];
        $filter[3][] = [$fieldLaunchTo, $today, 'gt'];
        $filter[4][] = [$fieldLaunchFrom, $today, 'eq'];
        $filter[4][] = [$fieldLaunchTo, $today, 'eq'];
        //Current date > Launch date from & Launch date to <= Current date
        $filter[5][] = [$fieldLaunchFrom, $today, 'lt'];
        $filter[5][] = [$fieldLaunchTo, $today, 'lteq'];
        $countFilter = count($filter);
        for ($i = 1; $i <= $countFilter; $i++) {
            $criteria = $this->_criteriaBuilder
                ->addFilter('status', $statusEnable, 'eq')
                ->addFilter($filter[$i][0][0], $filter[$i][0][1], $filter[$i][0][2])
                ->addFilter($filter[$i][1][0], $filter[$i][1][1], $filter[$i][1][2])
                ->create();
            $producstCollection = $this->_productRepository->getList($criteria);
            if ($producstCollection->getItems()) {
                foreach ($producstCollection->getItems() as $_product) {
                    try {
                        $this->_logger->info('Update status to disable products SKU #'.$_product->getSku());
                        $_product->setStatus($statusDisabled)->save();
                    } catch (\Exception $e)
                    {
                        $this->_logger->critical($e->getMessage());
                   }
                }
            }
        }
    }
}
