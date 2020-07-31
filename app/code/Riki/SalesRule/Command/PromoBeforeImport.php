<?php
namespace Riki\SalesRule\Command;

use Magento\Catalog\Model\Product;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

class PromoBeforeImport extends Command
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

    protected $state;


    protected $_varDirectory;
    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $_fileSystem;


    public function __construct(
        \Magento\Framework\File\Csv $reader,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\App\State $state,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
        \Magento\Framework\Filesystem $filesystem

    )
    {
        $this->_readerCSV = $reader;
        $this->_time      = $timezoneInterface;
        $this->state = $state;
        $this->_fileSystem   = $filesystem;
        $this->_varDirectory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);

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
                'Name of file to import 2'
            )
        ];
        $this->setName('riki:promo:before-import')
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
                }else if($type=='date'){
                    //date yyyy/mm/dd
                    $value=str_replace('/','-',$value);
                    if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$value))
                    {
                        return $this->_time->date($value)->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
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
            $data['error'][] = "\tUpdate date is invalid";
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
        $dataImport['from_date']          = $this->checkColumExit('ECOUPON_START_DATETIME',$data,'datetime');
        $dataImport['to_date']          = $this->checkColumExit('ECOUPON_END_DATETIME',$data,'datetime');
        $dataImport['promo_updated_at']          = $this->checkColumExit('UPDATED_DATETIME',$data,'datetime');
        $dataImport['is_active']          = $this->checkColumExit('VALID_FLG',$data,'int');
        $dataImport['coupon_code']          = $this->checkColumExit('ECOUPON_ID',$data);
       
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
                        $output->writeln("------------------------------------------------------------------------------------");
                        $output->writeln("[Row $row] Validate successfully!\n");
                    }
                    $row++;
                }

                if($totalError==0){
                    $output->writeln("===========================================================================================");
                    $output->writeln("\t\tValidate file successfully \n");
                    $output->writeln("===========================================================================================");
                }else{
                    $output->writeln("\n\n===========================================================================================");
                    $output->writeln("\n\tValidate error \n");
                    $output->writeln("===========================================================================================");
                }
            }catch (\Exception $e){
                $output->writeln( $e->getMessage());
                exit();
            }
        }
    }
    public function  getCoupon($dataSku,$code){
        $arraySku = [];
        foreach ($dataSku as $sku){
            if($sku['ECOUPON_ID'] == $code) {
                $arraySku[] = $sku['SKU_CODE'];
            }
        }
        return implode(",",$arraySku);
    }
}