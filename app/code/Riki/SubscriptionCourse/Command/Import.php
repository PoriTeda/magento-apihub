<?php
namespace Riki\SubscriptionCourse\Command;


use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Symfony\Component\Console\Input\InputArgument;
class Import extends AbstractCommand
{
    /**
     * @var \Riki\SubscriptionCourse\Model\Course $model
     */
    protected $model;

    /**
     * @var \Magento\Framework\File\Csv $_readerCSV
     */
    protected $_readerCSV;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface $_time
     */
    protected $_time;

    const FILE_NAME ='file_name';
    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;
    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $courseFactory;

    /**
     * Import constructor.
     * @param \Riki\SubscriptionCourse\Model\Course $model
     * @param \Magento\Framework\File\Csv $reader
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface
     */
    public function __construct(
        \Riki\SubscriptionCourse\Model\Course $model,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Magento\Framework\File\Csv $reader,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
        \Magento\Framework\App\State $state
    )
    {
        $this->model = $model;
        $this->_readerCSV = $reader;
        $this->_time = $timezoneInterface;
        $this->appState = $state;
        $this->courseFactory = $courseFactory;
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
            )
        ];
        $this->setName('riki:import:subscriptioncourse')
            ->setDescription('A cli Import Subscription Coursea')
            ->setDefinition($options);
        parent::configure();
    }

    /**
     * Check real date input.Only allow format d/m/Y
     *
     * @param $value
     * @return bool
     */
    function checkRealDateInput($value)
    {
        $dataDate = explode('/',$value);
        if (count($dataDate) !=3){
            return false;
        }
        $day   = $dataDate[0];
        $month = $dataDate[1];
        $year  = $dataDate[2];
        return checkdate($month,$day,$year);
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
                    if ( !$this->checkRealDateInput($value) ){
                        return null;
                    }else{
                        $value=str_replace('/','-',$value);
                        return $this->_time->date($value)->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
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
     * validate colum basic
     *
     * @param $errors
     * @param $modelCourse
     * @return array
     */
    public function validateColum($errors,$modelCourse){
        //check course code for migration
        if ($modelCourse->hasData('course_code')) {
            if($modelCourse->getData('course_code') ==''){
                $errors[] =__('Course code is invalid');
            }
        }else{
            $errors[] =__('Course code is invalid');
        }

        //check course_name
        if ($modelCourse->hasData('course_name')) {
            if($modelCourse->getData('course_name') ==''){
                $errors[] =__('Course name is invalid');
            }
        }else{
            $errors[] =__('Course name is invalid');
        }

        //check launch_date
        if ($modelCourse->hasData('launch_date')) {
            $launchDate  = $modelCourse->getData('launch_date');
            if( $launchDate ==''){
                $errors[] =__('Launch date is invalid');
            }else if ( $launchDate=='0000-00-00' || $launchDate=='00-00-0000' || $launchDate =='00-00-0000 00:00:00'  || $launchDate =='0000-00-00 00:00:00' ){
                $errors[] =__('Launch date is invalid');
            }
        }else{
            $errors[] =__('Launch date is invalid');
        }

        //check close date

        if ($modelCourse->hasData('close_date')){
            $closeDate = $modelCourse->getData('close_date');
            if ($closeDate==''){
                $errors[] =__('Close date is invalid');
            }else if ( $closeDate=='0000-00-00' || $closeDate=='00-00-0000' || $closeDate =='00-00-0000 00:00:00'  || $closeDate =='0000-00-00 00:00:00' ){
                $errors[] =__('Close date is invalid');
            }
        }else{
            $errors[] =__('Close date is invalid');
        }

        return $errors;
    }

    /**
     * validate frequency
     *
     * @param $errors
     * @param $modelCourse
     * @return array
     */
    public function validateFrequency($errors,$modelCourse){
        $strFrequency =$modelCourse->getData('frequency_ids');
        if($modelCourse->hasData('frequency_ids') && $strFrequency !=null){
            $strFrequency = strtolower($strFrequency);
            if(strpos($strFrequency,'month') && strpos($strFrequency,'week')){
                if(strpos($strFrequency,'months')){
                    $arrData = explode('months',$strFrequency);
                }else if(strpos($strFrequency,'month')){
                    $arrData = explode('month',$strFrequency);
                }

                $errMessage = null;
                $arrIds     = array();
                if(isset($arrData[0])){
                    //process months
                    $dataFrequency = $modelCourse->checkFrequency('month',trim($arrData[0]) .' months',array('months','month'));
                    if($dataFrequency ==null){
                        $errMessage =__('Frequency is invalid');
                    }else{
                        $arrIds= $dataFrequency;
                    }
                }
                if(isset($arrData[1])){
                    //process weeks
                    $dataFrequency = $modelCourse->checkFrequency('week',trim($arrData[1]) .' weeks',array('weeks','week'));
                    if($dataFrequency ==null){
                        $errMessage =__('Frequency is invalid');
                    }else{
                        $arrIds = array_merge($dataFrequency,$arrIds);
                    }
                }

                if($errMessage !=null){
                    $errors[] =__('Frequency id is invalid');
                }else{
                    $modelCourse->setData('frequency_ids',$arrIds);
                }
            }else{
                if(strpos($strFrequency,'month')){
                    $dataFrequency = $modelCourse->checkFrequency('month',$strFrequency,array('months','month'));
                    if($dataFrequency ==null){
                        $errors[] =__('Frequency is invalid');
                    }else{
                        $modelCourse->setData('frequency_ids',$dataFrequency);
                    }
                }else if(strpos($strFrequency,'week')){
                    $dataFrequency = $modelCourse->checkFrequency('week',$strFrequency,array('weeks','week'));
                    if($dataFrequency ==null){
                        $errors[] =__('Frequencyis invalid');
                    }else{
                        $modelCourse->setData('frequency_ids',$dataFrequency);
                    }
                }else{
                    $errors[] =__('Frequency is invalid');
                }
            }
        }else{
            $errors[] =__('Frequency is invalid');
        }

        return $errors;
    }

    /**
     * validate category
     *
     * @param $errors
     * @param $modelCourse
     * @return array
     */
    public function validateCategory($errors,$modelCourse){
        if($modelCourse->hasData('category_ids')){
            $categoryIds = $modelCourse->getData('category_ids');
            if(is_array($categoryIds)&& count($categoryIds)>0){
                $arrCatError = array();
                foreach ($categoryIds as $catId){
                    $categoryExit = $modelCourse->checkCategoryExit($catId);
                    if(!$categoryExit){
                        $arrCatError[] = $catId;
                    }
                }
                if(count($arrCatError)>0){
                    $errors[] =__('Category id (' .implode(',',$arrCatError). ') is invalid');
                }
            }else{
                $errors[] =__('Category is invalid');
            }
        }else{
            $errors[] =__('Category is invalid');
        }
        return $errors;
    }

    /**
     * validate website id
     *
     * @param $errors
     * @param $modelCourse
     * @return array
     */
    public function validateWebsite($errors,$modelCourse){
        if($modelCourse->hasData('website_ids') && $modelCourse->getData('website_ids') !='' ){
            $websiteIds = $modelCourse->getData('website_ids');
            if(is_array($websiteIds)&&count($websiteIds)>0){
                $arrWebsites = array();
                foreach ($websiteIds as $storeId){
                    $websiteExit = $modelCourse->checkWebSiteId($storeId);
                    if(!$websiteExit){
                        $arrWebsites[] = $storeId;
                    }
                }
                if(count($arrWebsites)>0){
                    $errors[] =__('Website id (' .implode(',',$arrWebsites). ') is invalid');
                }
            }else{
                $errors[] =__('Website is invalid');
            }
        }else{
            $errors[] =__('Website is invalid');
        }
        return $errors;
    }

    /**
     * validate payment code
     *
     * @param $errors
     * @param $modelCourse
     * @return array
     */
    public function validatePaymentCode($errors,$modelCourse){
        if($modelCourse->hasData('payment_ids') && $modelCourse->getData('payment_ids') !='' ){
            $paymentIds = $modelCourse->getData('website_ids');
            if(is_array($paymentIds)&&count($paymentIds)>0){
                $arrPaymentId = array();
                foreach ($paymentIds as $storeId){
                    $websiteExit = $modelCourse->checkWebSiteId($storeId);
                    if(!$websiteExit){
                        $arrPaymentId[] = $storeId;
                    }
                }
                if(count($arrPaymentId)>0){
                    $errors[] =__('Payment id (' .implode(',',$arrPaymentId). ') is invalid');
                }
            }else{
                $errors[] =__('Payment is invalid');
            }
        }
        return $errors;
    }
    

    /**
     * validate data
     * @param $modelCourse
     * @return array
     */
    public function validateDataImport($modelCourse){
        $errors = [];
        $errors = $this->validateColum($errors,$modelCourse);
        $errors = $this->validateFrequency($errors,$modelCourse);
        $errors = $this->validateCategory($errors,$modelCourse);
        $errors = $this->validateWebsite($errors,$modelCourse);
        $errors = $this->validatePaymentCode($errors,$modelCourse);
        return $errors;
    }

    /**
     * Check subscription course code exit
     *
     * @param $model
     * @param $courseCode
     *
     * @return \Magento\Framework\DataObject
     */
    public function checkCourseCodeExit($model,$courseCode)
    {
        $courseModel = $this->courseFactory->create()->getCollection();
        $courseModel->addFieldToFilter('course_code', $courseCode);
        if($courseModel->getSize()>0){
            return $courseModel->getFirstItem();
        }
        return $model;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param $data
     * @param $row
     */
    public function saveSubscriptionCourse(InputInterface $input, OutputInterface $output,$data,$row){
        $model = $this->model;
        $model = $this->checkCourseCodeExit($model,$data['course_code']);
        $model->addData($data);

        $errors = $model->validate();
        $errorImport = $this->validateDataImport($model);
        if(count($errorImport)>0){
            $errors = array_merge($errors,$errorImport);
        }

        if(count($errors)){
            $output->writeln("\n---------------------------------------");
            $output->writeln(
                "Row[$row] Validate error!"
            );
            $output->writeln(
                $errors
            );
        }else{
            try {
                $model->save();
                $name = $model->getName();
                $output->writeln("\n---------------------------------------");
                $output->writeln(
                    "Row[$row] Subscription Course name:'$name' was import successfully!\n"
                );
                $model->setId(null);

            } catch (\Exception $e) {

                $output->writeln(
                    $e->getMessage()
                );
                exit();
            }
        }
    }

    /**
     * Import subscription course
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->appState->setAreaCode('adminhtml');
        $fileName = $input->getArgument(self::FILE_NAME);
        if($fileName != ""){
            try {
                $dataResult = array();
                $dataRow = array();
                $dataCsv = $this->_readerCSV->getData($fileName);

                foreach ($dataCsv as $key => $value) {
                    if ($key == 0)
                        continue;
                    foreach ($value as $k => $v) {
                        if (isset($dataCsv[0][$k])) {
                                $dataRow[$dataCsv[0][$k]] = $v;
                        }
                    }
                    $dataResult[] = $dataRow;
                }
                $row =2;
                foreach ($dataResult as $key=> $data){
                    // convert Data
                    $dataImport['course_code']                    		   = $this->checkColumExit('course_code',$data);
                    $dataImport['course_name']             		   = $this->checkColumExit('course_name',$data);
                    $dataImport['subscription_type']       		   = strtolower($this->checkColumExit('subscription_type',$data));
                    $dataImport['frequency_ids']           		   = $this->checkColumExit('frequency_ids',$data);
                    $dataImport['hanpukai_type']           		   = $this->checkColumExit('hanpukai_type',$data);
                    $dataImport['hanpukai_maximum_order_times']    = $this->checkColumExit('hanpukai_maximum_order_times',$data,'int');
                    $dataImport['hanpukai_delivery_date_allowed']  = $this->checkColumExit('hanpukai_delivery_date_allowed',$data);
                    $dataImport['duration_unit']        		   = $this->checkColumExit('duration_unit',$data);
                    $dataImport['duration_interval']    		   = $this->checkColumExit('duration_interval',$data);
                    $dataImport['must_select_sku']      		   = $this->checkColumExit('must_select_sku',$data);
                    $dataImport['minimum_order_qty']    		   = $this->checkColumExit('minimum_order_qty',$data);
                    $dataImport['minimum_order_times']  		   = $this->checkColumExit('minimum_order_times',$data);
                    $dataImport['sales_count']          		   = $this->checkColumExit('sales_count',$data);
                    $dataImport['sales_value_count']    		   = $this->checkColumExit('sales_value_count',$data);
                    $dataImport['application_limit']    		   = $this->checkColumExit('application_limit',$data);
                    $dataImport['payment_ids']          		   = $this->checkColumExit('payment_ids',$data);
                    $dataImport['membership_ids']       		   = $this->checkColumExit('membership_ids',$data);
                    $dataImport['description']              	   = $this->checkColumExit('description',$data);
                    $dataImport['is_enable']              	       = $this->checkColumExit('is_enable',$data);
                    $dataImport['allow_skip_next_delivery'] 	   = $this->checkColumExit('allow_skip_next_delivery',$data);
                    $dataImport['launch_date']                     = $this->checkColumExit('launch_date',$data,'datetime');
                    $dataImport['close_date']                      = $this->checkColumExit('close_date',$data,'datetime');
                    $dataImport['meta_title']                      = $this->checkColumExit('meta_title',$data);
                    $dataImport['meta_keywords']                   = $this->checkColumExit('meta_keywords',$data);
                    $dataImport['meta_description']                = $this->checkColumExit('meta_description',$data);
                    $dataImport['penalty_fee']                     = $this->checkColumExit('penalty_fee',$data);
                    $dataImport['allow_change_next_delivery_date'] = $this->checkColumExit('allow_change_next_delivery_date',$data);
                    $dataImport['allow_change_payment_method']     = $this->checkColumExit('allow_change_payment_method',$data);
                    $dataImport['allow_change_address']     	   = $this->checkColumExit('allow_change_address',$data);
                    $dataImport['allow_change_product']     	   = $this->checkColumExit('allow_change_product',$data);
                    $dataImport['allow_change_qty']         	   = $this->checkColumExit('allow_change_qty',$data);
                    $dataImport['visibility']               	   = $this->checkColumExit('visibility',$data);

                    if(isset($data['category_ids']) && $data['category_ids'] !='') {
                        $categoryIds = explode(',',$this->checkColumExit('category_ids', $data));
                        $dataImport['category_ids'] = $categoryIds;
                    }else{
                        $dataImport['category_ids'] = null;
                    }

                    if(isset($data['website_ids']) && $data['website_ids'] !='') {
                        $web = explode(',', $this->checkColumExit('website_ids', $data));
                        $dataImport['website_ids'] =$web;
                    }
                    if(isset($data['payment_ids']) && $data['payment_ids'] !=''){
                        $pay = explode(',',$data['payment_ids']);
                        $data['payment_ids'] = $pay;
                    }

                    //set penalty_fee = 0 when is null
                    if ($dataImport['penalty_fee']==null){
                        $dataImport['penalty_fee'] = 0;
                    }

                    $this->saveSubscriptionCourse($input,$output,$dataImport,$row);

                    unset($dataResult[$key]);
                    $row++;
                }
            }catch (\Exception $e){
                $output->writeln(
                    $e->getMessage()
                );
                exit();
            }
        }
    }
}
