<?php
/**
 * Riki Basic Setup
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\BasicSetup\Setup
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\BasicSetup\Model;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Store\Model\Group;
use Magento\Framework\Registry;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Riki\BasicSetup\Helper\Data as DataHelper;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Psr\Log\LoggerInterface;
/**
 * Class CategorySetup
 *
 * @category  RIKI
 * @package   Riki\BasicSetup\Setup
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class CategorySetup
{
    CONST DEFAULT_CATEGORY_ID = 2;

    CONST DEFAULT_CATEGORYL2_ID = 236;
    /**
     * @var CategoryFactory
     */
    protected $categoryModelFactory;
    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;
    /**
     * @var
     */
    protected $registry;
    /**
     * @var
     */
    protected $groupManager;
    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepositoryInterface;
    /**
     * @var \Riki\BasicSetup\Helper\Data
     */
    protected $dataHelper;
    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchBuilder;
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * CategorySetup constructor.
     * @param CategoryFactory $categoryFactory
     * @param CollectionFactory $categoryRepository
     * @param Group $groupManager
     * @param CategoryRepositoryInterface $categoryRepositoryInterface
     * @param Registry $registry
     * @param DataHelper $datahelper
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param LoggerInterface $logger
     */
    public function __construct
    (
        CategoryFactory $categoryFactory,
        CollectionFactory $categoryRepository,
        Group $groupManager,
        CategoryRepositoryInterface $categoryRepositoryInterface,
        Registry $registry,
        DataHelper $datahelper,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        LoggerInterface $logger
    )
    {
        $this->categoryModelFactory = $categoryFactory;
        $this->categoryRepository = $categoryRepository;
        $this->groupManager = $groupManager;
        $this->categoryRepositoryInterface = $categoryRepositoryInterface;
        $this->registry = $registry;
        $this->dataHelper = $datahelper;
        $this->searchBuilder = $searchCriteriaBuilder;
        $this->logger = $logger;
    }

    /**
     * @param $version
     * @throws \Exception
     */
    public function categorySetup($version)
    {
        /*
         * Columns in CSV data
         * 0: Japanese category name
         * 1: English category Name
         * 2: Url - key
         * 3: Level
         * 4: Parent
         */
        //remove old category
        $fileData = $version.'/'.DataHelper::FILE_ADMIN_CATEGORIES;
        $categoriesData = $this->dataHelper->getCsvContent($fileData);
        $categoriesData = $this->ensureArray($categoriesData);
        $this->removelOldCategories($version);
        foreach($categoriesData as $catData)
        {
            //get Category parent ID
            $catParentIds = $this->getL2Categories();
            if($catData[4] && array_key_exists($catData[4],$catParentIds))
            {
                $parentId = $catParentIds[$catData[4]];
            }
            else
            {
                $parentId = self::DEFAULT_CATEGORY_ID;
            }
            $data = [
                'data' => [
                    "parent_id" => $parentId,
                    'name' => $catData[0],
                    "is_active" => true,
                    "include_in_menu" => false,
                    "url_key" => $catData[1],
                    "url_path" => $catData[1],
                    "store_id" => 0
                ],
                'custom_attributes' => [
                    "display_mode"=> "PRODUCTS",
                    "is_anchor"=> "1",
                ],
                'parent_id' => $parentId
            ];
            $categoryModel = $this->categoryModelFactory->create();
            $categoryModel->setParentId($parentId);
            $categoryModel->setData($data);
            $categoryModel->setUrlKey($catData[1]);
            try {
                $this->categoryRepositoryInterface->save($categoryModel);
            } catch (\Exception $e ) {
                $this->logger->critical($e->getMessage());
            }
        }

    }//end function

    /**
     * @throws \Exception
     */
    public function removelOldCategories($version)
    {

        $fileData = $version.'/'.DataHelper::FILE_ADMIN_CATEGORIES_REMOVE;
        $compareCateNameArr = $this->dataHelper->getCsvContent($fileData);
        $compareCateNameArr = $this->ensureArrayFirstOnly($compareCateNameArr);

        if(!$this->registry->registry('isSecureArea'))
        {
            $this->registry->register('isSecureArea', true);
        }
        $allcats = $this->categoryRepository->create();
        $allcats->addAttributeToSelect('name');
        if($allcats->getSize())
        {
            foreach($allcats as $cat)
            {
                if(in_array($cat->getName(),$compareCateNameArr))
                {
                    try {
                        $cat->delete();
                    } catch(\Exception $e)
                    {
                        $this->logger->critical($e->getMessage());
                    }

                }
            }
        }

    }

    /**
     * @param $array
     * @return mixed
     */
    public function ensureArray($array)
    {
        $countArray = count($array);
        for($i=0;$i<$countArray;$i++)
        {
            for($j=0;$j<4;$j++)
            {
                if(!array_key_exists($j,$array[$i]))
                {
                    $array[$i][$j] = '';
                }
            }
        }
        return $array;
    }
    /**
     * @param $array
     * @return mixed
     */
    public function ensureArrayFirstOnly($array)
    {
        $newArray = array();
        $countArray = count($array);
        for($i=0;$i<$countArray;$i++)
        {
            $newArray[] = $array[$i][0];
        }
        return $newArray;
    }
    /**
     * @return array
     */
    public function getL2Categories()
    {
        $collection = $this->categoryRepository->create();
        $collection->addAttributeToSelect('name');
        $collection->addFieldToFilter('level',2);
        $catLevel2 = array();
        if($collection->getSize())
        {
            foreach($collection as $cat)
            {
                $catLevel2[$cat->getName()]=$cat->getId();
            }
        }
        return $catLevel2;
    }

    /**
     * @param $categoryName
     * @return bool
     */
    public function getExistCategory($categoryName)
    {
        $collection = $this->categoryRepository->create();
        $collection->addFieldToFilter('name',$categoryName);
        $collection->setOrder('entity_id','ASC');
        if($collection->getSize())
        {
            return $collection->getFirstItem()->getId();
        }
        return false;
    }
    public function removeDuplicateCategories()
    {
        if(!$this->registry->registry('isSecureArea'))
        {
            $this->registry->register('isSecureArea', true);
        }
        $allcats = $this->categoryRepository->create();
        $allcats->addFieldToFilter('entity_id', array('gt'=>2));
        $allcats->addAttributeToSelect('name');
        $groupCats = array();
        $indexCats = array();
        if($allcats->getSize())
        {
            foreach($allcats as $cat)
            {
                $groupCats[$cat->getName()][] = $cat->getId();
                $indexCats[$cat->getId()] = $cat;
            }
        }
        if($groupCats)
        {
            foreach($groupCats as $groups)
            {
                if(count($groups)>1)
                {
                    //sort category acsending
                    asort($groups);
                    // remove first element
                    array_shift($groups);
                    foreach($groups as $cat)
                    {
                        try{
                            if(array_key_exists($cat,$indexCats))
                            {
                                $indexCats[$cat]->delete();
                            }
                        }catch(\Exception $e)
                        {
                            $this->logger->critical($e->getMessage());
                        }
                    }

                }
            }
        }
    }
}//end class