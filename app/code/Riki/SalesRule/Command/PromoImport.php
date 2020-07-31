<?php
namespace Riki\SalesRule\Command;

use Magento\Catalog\Model\Product;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\SalesRule\Api\Data\RuleInterface;

class PromoImport extends Command
{



    /**
     * @var \Magento\Framework\File\Csv $_readerCSV
     */
    protected $_readerCSV;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface $_time
     */
    protected $_time;

    const FILE_NAME ='file_name';
    const FILE_NAME_2 ='file_name_2';

    protected $productFactory;

    protected $_state;


    protected $_varDirectory;
    /**
     * @var \Magento\Framework\Filesystem
     */
    /**
     * @var SearchCriteriaBuilder
     */
    protected $_searchBuilder;

    /**
     * @var \Magento\SalesRule\Model\Rule\Condition\Product\Found
     */
    protected $_foundProductRule;
    /**
     * @var \Magento\SalesRule\Model\Rule\Condition\Product
     */
    protected $_productRule;
    /**
     * @var \Magento\SalesRule\Api\Data\RuleInterfaceFactory
     */
    protected $_ruleRepository;
    /**
     * @var \Magento\SalesRule\Api\Data\ConditionInterfaceFactory
     */
    protected $_conditionRepository;
    /**
     * @var \Magento\SalesRule\Api\RuleRepositoryInterface
     */
    protected $_ruleRepositoryInterface;
    /**
     * @var \Magento\SalesRule\Api\Data\CouponInterfaceFactory
     */
    protected $_couponInterfaceFactoryData;
    /**
     * @var \Magento\SalesRule\Api\CouponRepositoryInterface
     */
    protected $_couponRepositoryInterface;
    /**
     * @var \Magento\SalesRule\Model\RuleFactory
     */
    protected $_ruleFactory;
    protected $_groupRepositoryInterface;
    protected $_storeManagerInterface;
    protected $_websiteRepositoryInterface;
    /**
     * PromoImport constructor.
     * @param \Magento\Framework\File\Csv $reader
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Framework\App\State $state
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface
     * @param \Magento\SalesRule\Api\Data\RuleInterfaceFactory $ruleRepository
     * @param \Magento\SalesRule\Model\Rule\Condition\Product\Found $foundProductRule
     * @param \Magento\SalesRule\Model\Rule\Condition\Product $productRule
     * @param \Magento\SalesRule\Api\Data\ConditionInterfaceFactory $conditionRepository
     * @param \Magento\SalesRule\Api\RuleRepositoryInterface $ruleRepositoryInterface
     * @param \Magento\SalesRule\Api\Data\CouponInterfaceFactory $couponInterfaceFactoryData
     * @param \Magento\SalesRule\Api\CouponRepositoryInterface $couponRepositoryInterface
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        \Magento\Framework\File\Csv $reader,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\App\State $state,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\SalesRule\Model\RuleFactory $ruleFactory,
        \Magento\SalesRule\Api\Data\RuleInterfaceFactory $ruleRepository,
        \Magento\SalesRule\Model\Rule\Condition\Product\Found $foundProductRule,
        \Magento\SalesRule\Model\Rule\Condition\Product  $productRule,
        \Magento\SalesRule\Api\Data\ConditionInterfaceFactory $conditionRepository,
        \Magento\SalesRule\Api\RuleRepositoryInterface $ruleRepositoryInterface,
        \Magento\SalesRule\Api\Data\CouponInterfaceFactory $couponInterfaceFactoryData,
        \Magento\SalesRule\Api\CouponRepositoryInterface $couponRepositoryInterface,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Customer\Api\GroupRepositoryInterface $groupRepositoryInterface,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Store\Api\WebsiteRepositoryInterface $websiteRepositoryInterface



    )
    {
        $this->_readerCSV = $reader;
        $this->_time      = $timezoneInterface;
        $this->_state = $state;
        $this->_varDirectory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->_ruleRepository = $ruleRepository;
        $this->_conditionRepository = $conditionRepository;
        $this->_foundProductRule = $foundProductRule;
        $this->_productRule = $productRule;
        $this->_ruleRepositoryInterface = $ruleRepositoryInterface ;
        $this->_couponInterfaceFactoryData = $couponInterfaceFactoryData;
        $this->_couponRepositoryInterface = $couponRepositoryInterface;
        $this->_searchBuilder = $searchCriteriaBuilder;
        $this->_ruleFactory = $ruleFactory;
        $this->_groupRepositoryInterface = $groupRepositoryInterface;
        $this->_websiteRepositoryInterface = $websiteRepositoryInterface;


        parent::__construct();
    }

    /**
     * Set param name for CLI
     */
    protected function configure()
    {
        $options = [
            new InputArgument(
                self::FILE_NAME,
                InputArgument::REQUIRED,
                'Name of file to import'
            ),new InputArgument(
                self::FILE_NAME_2,
                InputArgument::REQUIRED,
                'Name of file to import Product'
            )
        ];
        $this->setName('riki:promo:import')
            ->setDescription('A cli Import Promotion')
            ->setDefinition($options);
        parent::configure();
    }

    /**
     * check colum exit on file csv
     *
     * @param $fieldName
     * @param $data
     * @param null $type
     * @return int|null
     */
    public function checkColumExit($fieldName,$data,$type=null){
        if(isset($data[$fieldName]) && $data[$fieldName] !=''){
            $value = trim($data[$fieldName]);
            if($type !=null){
                if($type=='datetime'){
                    //date yyyy/mm/dd h:i:s
                    $value=str_replace('/','-',$value);
                    $re1='((?:2|1)\\d{3}(?:-|\\/)(?:(?:0[1-9])|(?:1[0-2]))(?:-|\\/)(?:(?:0[1-9])|(?:[1-2][0-9])|(?:3[0-1]))(?:T|\\s)(?:(?:[0-1][0-9])|(?:2[0-3])):(?:[0-5][0-9]):(?:[0-5][0-9]))';    # Time Stamp 1
                    if ($c=preg_match_all ("/".$re1."/is", $value, $matches))
                    {
                        return $this->_time->date($value)->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
                    }else{
                        return null;
                    }

                }if($type=='onlytime'){
                    //date yyyy/mm/dd h:i:s
                    $value=str_replace('/','-',$value);
                    $re1='((?:2|1)\\d{3}(?:-|\\/)(?:(?:0[1-9])|(?:1[0-2]))(?:-|\\/)(?:(?:0[1-9])|(?:[1-2][0-9])|(?:3[0-1]))(?:T|\\s)(?:(?:[0-1][0-9])|(?:2[0-3])):(?:[0-5][0-9]):(?:[0-5][0-9]))';    # Time Stamp 1
                    if ($c=preg_match_all ("/".$re1."/is", $value, $matches))
                    {
                        return $this->_time->date($value)->setTimezone(new \DateTimeZone('UTC'))->format('H:i:s');
                    }else{
                        return null;
                    }

                }
                else if($type=='date'){
                    //date yyyy/mm/dd
                    $value=str_replace('/','-',$value);
                    $re1='((?:2|1)\\d{3}(?:-|\\/)(?:(?:0[1-9])|(?:1[0-2]))(?:-|\\/)(?:(?:0[1-9])|(?:[1-2][0-9])|(?:3[0-1]))(?:T|\\s)(?:(?:[0-1][0-9])|(?:2[0-3])):(?:[0-5][0-9]):(?:[0-5][0-9]))';    # Time Stamp 1
                    if ($c=preg_match_all ("/".$re1."/is", $value, $matches))
                    {
                        return $this->_time->date($value)->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d');
                    }else{
                        return null;
                    }
                }else if($type=='int'){
                    if($value>=0){
                        return $value;
                    }else{
                        return null;
                    }
                }
            }else{
                return $value;
            }
        }
        return null;
    }

    /**
     * @param $dataImport
     * @return string[]
     */
    public function validateData($dataImport)
    {
        $data = array(
            'error'=>null,
            'dataImport'  =>$dataImport
        );

        if($dataImport['name'] ==null){
            $data['error'][] = "\tName is invalid";
        }
        if($dataImport['from_date'] ==null){
            $data['error'][] = "\tFrom date is invalid";
        }
        if($dataImport['to_date'] ==null){
            $data['error'][] = "\tTo day  is invalid";
        }
        if($dataImport['to_date'] ==null){
            $data['error'][] = "\tUdate date is invalid";
        }
        if($dataImport['coupon_code'] ==null){
            $data['error'][] = "\tCoupon code  is invalid";
        }


        return $data;
    }

    /**
     * show message
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param $error
     * @param $row
     */
    public function showMessageError(
        InputInterface $input,
        OutputInterface $output,
        $error,
        $row
    ){
        $output->writeln("------------------------------------------");
        $output->writeln("[Row $row] Validate error!\n");
        $output->writeln($error."\n");
    }

    /**
     * Remove BOM from a file
     *
     * @param string $sourceFile
     * @return $this
     */
    public function removeBom($sourceFile)
    {
        $sourceFile = str_replace('var/','',$sourceFile);
        $string = $this->_varDirectory->readFile($this->_varDirectory->getRelativePath($sourceFile));
        if ($string !== false && substr($string, 0, 3) == pack("CCC", 0xef, 0xbb, 0xbf)) {
            $string = substr($string, 3);
            $this->_varDirectory->writeFile($this->_varDirectory->getRelativePath($sourceFile), $string);
        }
        return $this;
    }




    /**
     * convert data import
     *
     * @param $data
     */
    public function convertDataImport($data){
        $dataImport = array();
        $dataImport['name']               = $this->checkColumExit('ECOUPON_NAME_PC',$data);
        $dataImport['from_date']          = $this->checkColumExit('ECOUPON_START_DATETIME',$data,'date');
        $dataImport['to_date']          = $this->checkColumExit('ECOUPON_END_DATETIME',$data,'date');
        $dataImport['from_time']          = $this->checkColumExit('ECOUPON_START_DATETIME',$data,'onlytime');
        $dataImport['to_time']          = $this->checkColumExit('ECOUPON_END_DATETIME',$data,'onlytime');
        $dataImport['promo_updated_at']          = $this->checkColumExit('UPDATED_DATETIME',$data,'datetime');
        $dataImport['is_active']          = $this->checkColumExit('VALID_FLG',$data,'int');
        $dataImport['coupon_code']          = $this->checkColumExit('ECOUPON_ID',$data);
        $dataImport['create_time']          = $this->checkColumExit('CREATED_DATETIME',$data,'datetime');

        return $dataImport;
    }
    public function convertDataImportProduct($data){
        $dataImport = array();
        $dataImport['ECOUPON_ID']               = $this->checkColumExit('ECOUPON_ID',$data);
        $dataImport['SKU_CODE']          = $this->checkColumExit('SKU_CODE',$data);
        $dataImport['ECOUPON_RATE']          = $this->checkColumExit('ECOUPON_RATE',$data);

        return $dataImport;
    }
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_state->setAreaCode('frontend');
        
        $fileContent = $input->getArgument(self::FILE_NAME);
        $fileProduct = $input->getArgument(self::FILE_NAME_2);
        $dataResultProduct = array();
        if($fileProduct != ""){
            try {
                $dataRowProduct = array();

                $this->removeBom($fileProduct);
                $dataCsvProduct = $this->_readerCSV->getData($fileProduct);

                foreach ($dataCsvProduct as $key => $value) {
                    if ($key == 0) continue;
                    foreach ($value as $k => $v) {
                        if(isset($dataCsvProduct[0][$k])) {
                            $keyColum = str_replace('"','', $dataCsvProduct[0][$k]);
                            $dataRowProduct[trim($keyColum)] = $v;
                        }
                    }
                    $dataResultProduct[] = $dataRowProduct;
                }



            }catch (\Exception $e){
                $output->writeln( $e->getMessage());
                exit();
            }
        }

        if($fileContent != ""){
            try {
                $dataResultContent = array();
                $dataRow = array();

                $this->removeBom($fileContent);
                $dataCsvContent = $this->_readerCSV->getData($fileContent);

                foreach ($dataCsvContent as $key => $value) {
                    if ($key == 0) continue;
                    foreach ($value as $k => $v) {
                        if(isset($dataCsvContent[0][$k])) {
                            $keyColum = str_replace('"','', $dataCsvContent[0][$k]);
                            $dataRow[trim($keyColum)] = $v;
                        }
                    }
                    $dataResultContent[] = $dataRow;
                }

                $row = 2;
                $totalError = 0;
                foreach ($dataResultContent as $data){
                    // convert Data
                    $dataImport = $this->convertDataImport($data);

                    //validate data
                    $dataBeforeImport = $this->validateData($dataImport);
                    $errors       = $dataBeforeImport['error'];

                    if(count($errors)>0){
                        $output->writeln("\n------------------------------------------------------------------------------------");
                        $output->writeln("[Row $row] Validate error!\n");
                        $output->writeln($errors);
                        $totalError++;
                    }else{
                        //
                        $dateRule = $dataBeforeImport['dataImport'];
                        $listSKu = $this->getCoupon($dataResultProduct,$dateRule['coupon_code']);
                        // Check coupon code exist
                        $filter = $this->_searchBuilder
                            ->addFilter('code', $dateRule['coupon_code'], 'eq');
                        $collectionCoupon = $this->_couponRepositoryInterface->getList($filter->create());

                        // Data default
                        $listGroup = $this->getGroupList();
                        $listWebsite = $this->getWebsiteList();

                        if(!$collectionCoupon->getTotalCount()){
                            if($listSKu['skuList'] != '' ){
                                $shoppingCartPriceRule = $this->_ruleRepository->create();
                                $shoppingCartPriceRule->setName($dateRule['name'])
                                    ->setSortOrder(0)
                                    ->setFromDate($dateRule['from_date'])
                                    ->setToDate($dateRule['to_date'])
                                    ->setUsesPerCustomer('0')
                                    ->setCustomerGroupIds($listGroup)
                                    ->setIsActive($dateRule['is_active'])
                                    ->setIsAdvanced('1')
                                    ->setProductIds(NULL)
                                    ->setSimpleAction('by_percent')
                                    ->setDiscountAmount($listSKu['rate'])
                                    ->setDiscountQty(NULL)
                                    ->setDiscountStep('0')
                                    ->setSimpleFreeShipping('0')
                                    ->setApplyToShipping('0')
                                    ->setTimesUsed('0')
                                    ->setIsRss('0')
                                    ->setWebsiteIds($listWebsite)
                                    ->setCouponType(RuleInterface::COUPON_TYPE_SPECIFIC_COUPON);

                                $conditionList = [];
                                $actionsList = [];
                                // add condition
                                $conditionList[] = $this->_conditionRepository->create()
                                    ->setConditionType('Magento\SalesRule\Model\Rule\Condition\Product')
                                    ->setAttributeName('sku')
                                    ->setOperator('()')
                                    ->setValue($listSKu['skuList']);
                                $combineCondition = $this->_conditionRepository->create()
                                    ->setConditionType('Magento\SalesRule\Model\Rule\Condition\Product\Combine')
                                    ->setConditions($conditionList);
                                $shoppingCartPriceRule->setCondition($combineCondition);
                                // Add action
                                $actionsList[] = $this->_conditionRepository->create()
                                    ->setConditionType('Magento\SalesRule\Model\Rule\Condition\Product')
                                    ->setAttributeName('sku')
                                    ->setOperator('()')
                                    ->setValue($listSKu['skuList']);
                                $combinedAction  = $this->_conditionRepository->create()
                                    ->setConditionType('Magento\SalesRule\Model\Rule\Condition\Product\Combine')
                                    ->setConditions($actionsList);

                                $shoppingCartPriceRule->setActionCondition($combinedAction);
                                // Save rule info
                                $resultRule = $this->_ruleRepositoryInterface->save($shoppingCartPriceRule);
                                $ruleId = $resultRule->getRuleId();
                                $resultRuleTime = $this->_ruleFactory->create()->load($ruleId);
                                $resultRuleTime->setData('from_time',
                                    $dateRule['from_date'] . ' ' . $dateRule['from_time']);
                                $resultRuleTime->setData('to_time',
                                    $dateRule['from_date'] . ' ' . $dateRule['to_time']);
                                $resultRuleTime->setData('subscription',0);
                                $resultRuleTime->save();
                                // Save coupon
                                $couponData = $this->_couponInterfaceFactoryData->create();
                                $couponData
                                    ->setRuleId($ruleId)
                                    ->setCode($dateRule['coupon_code'])
                                    ->setType(0)
                                    ->setIsPrimary(true)
                                    ->getCreatedAt($dateRule['create_time']) ;
                                $this->_couponRepositoryInterface->save($couponData);
                            }else{
                                $output->writeln("\n------------------------------------------------------------------------------------");
                                $output->writeln($dateRule['coupon_code']." Not map information!\n");
                                $output->writeln($errors);
                                $totalError++;
                            }


                        }else{
                            $output->writeln("\n------------------------------------------------------------------------------------");
                            $output->writeln($dateRule['coupon_code']." Exist!\n");
                            $output->writeln($errors);
                            $totalError++;
                        }

                    }
                    $row++;
                }
                if($totalError == 0){
                    $output->writeln("===========================================================================================");
                    $output->writeln("\t\tImport file successfully \n");
                    $output->writeln("===========================================================================================");
                }
            }catch (\Exception $e){
                $output->writeln( $e->getMessage());
                exit();
            }
            catch (\Magento\Framework\Exception\LocalizedException $e) {
                $output->writeln( $e->getMessage());
                exit();
            }
        }
    }

    /**
     * @param $dataSku
     * @param $code
     * @return array
     */
    public function  getCoupon($dataSku,$code){
        $arraySku = [];
        $paramCoupon = [];
        $couponRate = 0;
        foreach ($dataSku as $sku){
            if($sku['ECOUPON_ID'] == $code) {
                $arraySku[] = $sku['SKU_CODE'];
                $couponRate = $sku['ECOUPON_RATE'];
            }
        }
        $paramCoupon['rate'] = $couponRate;
        $paramCoupon['skuList'] = implode(",",$arraySku);
        return $paramCoupon;
    }

    /**
     * get All group customer ID
     * @return array|null
     */
    public function getGroupList(){
        $arrayGroup = [];
        try {
            $listGroup = $this->_groupRepositoryInterface->getList($this->_searchBuilder->create());
            if($listGroup->getTotalCount()){
                foreach($listGroup->getItems() as $groupItem){
                    $arrayGroup[] = $groupItem->getId();
                }
            }
        }catch (\Magento\Framework\Exception\NoSuchEntityException $e){
            return null;
        }
        return $arrayGroup;
    }

    /**
     * Get all website Id
     * @return array
     */
    public function getWebsiteList(){
        $arrayWebsite = [];
        try{
            $webList = $this->_websiteRepositoryInterface->getList();
            if(count($webList) > 0){
                foreach ($webList as $web){
                    $arrayWebsite[] = $web->getId();
                }
            }
        }catch (\Magento\Framework\Exception\NoSuchEntityException $e){
            return null;
        }

        return $arrayWebsite;

}
}