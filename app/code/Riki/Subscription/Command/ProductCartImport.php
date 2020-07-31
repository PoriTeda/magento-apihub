<?php
namespace Riki\Subscription\Command;

use Magento\Catalog\Model\Product;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class ProductCartImport extends Command
{
    const FILE_NAME = 'file_name';

    const TYPE_PROFILE_CART = 'type_profile_cart';

    const MAIN_PROFILE_CART = 'main_profile_cart';

    const VERSION_PROFILE_CART = 'version_profile_cart';

    /**
     * @var \Riki\SubscriptionCourse\Model\Course $model
     */
    protected $_modelCourse;

    /**
     * @var \Riki\SubscriptionCourse\Model\Course
     */
    protected $_modelSubscriptionProfile;

    /**
     * @var \Magento\Framework\File\Csv $_readerCSV
     */
    protected $_readerCSV;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface $_time
     */
    protected $_time;

    /**
     * @var \Riki\SubscriptionCourse\Model\Course
     */
    protected $subscriptionCourse;
    /**
     * @var \Riki\Subscription\Model\ProductCart\ProductCart
     */
    protected $productCart;
    /**
     * @var \Magento\Customer\Api\Data\AddressInterfaceFactory
     */
    protected $resourceConnection;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;
    /**
     * @var ProductCartBeforeImport
     */

    protected $beforeValidateImport;

    protected $dataInsert = [];

    /**
     * ProductCartImport constructor.
     * @param \Riki\Subscription\Model\Profile\ProfileImport $modelSubscriptionProfile
     * @param \Magento\Framework\File\Csv $reader
     * @param \Riki\SubscriptionCourse\Model\Course $subscriptionCourse
     * @param \Riki\Subscription\Model\ProductCart\ProductCart $productCart
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface
     * @param ProductCartBeforeImport $beforeValidateImport
     */
    public function __construct(
        \Riki\Subscription\Model\Profile\ProfileImport $modelSubscriptionProfile,
        \Magento\Framework\File\Csv $reader,
        \Riki\SubscriptionCourse\Model\Course $subscriptionCourse,
        \Riki\Subscription\Model\ProductCart\ProductCart $productCart,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
        \Riki\Subscription\Command\ProductCartBeforeImport $beforeValidateImport
    )
    {
        $this->_modelSubscriptionProfile = $modelSubscriptionProfile;
        $this->_readerCSV = $reader;
        $this->_time = $timezoneInterface;
        $this->subscriptionCourse = $subscriptionCourse;
        $this->productCart = $productCart;
        $this->productFactory = $productFactory;
        $this->beforeValidateImport = $beforeValidateImport;
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
            ),
            new InputArgument(
                self::TYPE_PROFILE_CART,
                InputArgument::REQUIRED,
                'Check type profile cart'
            )
        ];
        $this->setName('riki:product-cart:import')
            ->setDescription('A cli Import Subscription Product Cart')
            ->setDefinition($options);
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $flagTime = microtime(true);

        $fileName = $input->getArgument(self::FILE_NAME);

        $typeProfileCart = $input->getArgument(self::TYPE_PROFILE_CART);

        if ($fileName != "") {
            try {

                list($dataResult, $aDataForValidated) = $this->beforeValidateImport->prepareData($fileName, $typeProfileCart);

                $row = 2;
                $totalError = 0;

                $iBulkData = 10000;
                $iCount = 0;
                $iCountBulk = 0;

                $dataMultipleImport = [];
                $outputMessage = [];

                //validate data
                $isValidateSuccess= true;
                $dataBeforeValidate = [];
                foreach ($dataResult as $data) {
                    $dataImport = $this->beforeValidateImport->convertDataImport($data);

                    //validate data
                    $dataBeforeImport = $this->beforeValidateImport->validateData($dataImport, $aDataForValidated, $typeProfileCart, $row);
                    $dataImport = $dataBeforeImport['dataImport'];
                    $dataBeforeValidate[] = $dataImport;

                    $errors = $dataBeforeImport['error'];
                    if (count($errors) > 0) {
                        $output->writeln("\n------------------------------------------------------------------------------------");
                        $output->writeln("[Row $row] Validate error!\n");
                        $output->writeln($errors);
                        $totalError++;
                        $isValidateSuccess =false;
                    }else{
                        $this->dataInsert[$dataImport['profile_id']][$dataImport['product_id']] = $dataImport;
                    }
                    $row++;
                }

                if (!$isValidateSuccess){
                    return false;
                }else{
                    $totalError = 0;
                    $row = 2;
                }


                foreach ($dataBeforeValidate as $dataImport) {
                    unset($dataImport['order_times']);
                    try {

                        if ($iCount >= $iBulkData) {

                            $iInsertedId = $this->beforeValidateImport->insertMultipleRecord('subscription_profile_product_cart', $dataMultipleImport);
                            if ($iInsertedId == count($dataMultipleImport)) {
                                $output->writeln(implode("\n", $outputMessage) . "\n");
                            } else {
                                $output->writeln("Missing record at bulk data " . $iBulkData . "\n");
                            }

                            $dataMultipleImport = [];
                            $outputMessage = [];
                            $iCount = 0;
                            $iCountBulk++;
                        }

                        $dataMultipleImport[] = $dataImport;
                        $outputMessage[] = "[Row $row] Product cart id of profile id " . $dataImport['profile_id'] . " was import successfully!\n";

                        $iCount++;

                    } catch (\Exception $e) {
                        $output->writeln($e->getMessage());
                        exit();
                    }
                    $row++;
                }

                if (!empty($dataMultipleImport)) {

                    $iInsertedId = $this->beforeValidateImport->insertMultipleRecord('subscription_profile_product_cart', $dataMultipleImport);

                    if ($iInsertedId == count($dataMultipleImport)) {
                        $output->writeln(implode("\n", $outputMessage) . "\n");
                    } else {
                        $output->writeln("Missing record at bulk data " . $iBulkData . "\n");
                    }
                    $iCountBulk += 1;
                }

                //update hanpukai quantity
                $dataHanpukaiDataUpdate = null;
                if (is_array($this->dataInsert)&& count($this->dataInsert)>0){
                    foreach ($this->dataInsert as $profileId =>$arrProduct){
                        $updateSuccess = $this->beforeValidateImport->checkHanpukaiQty($profileId,$arrProduct,$aDataForValidated);
                        if ($updateSuccess){
                            $output->writeln("Update data profile id :" . $profileId . "\n");
                        }
                    }
                }


            } catch (\Exception $e) {
                $output->writeln($e->getMessage());
                exit();
            }
        }

        $timeElapsedSecs = microtime(true) - $flagTime;
        echo "Script run time :" . ($timeElapsedSecs) . "\n";
    }
}