<?php
namespace Riki\Subscription\Command;

use Riki\Subscription\Model\Profile\Profile;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class SubscriptionImport extends Command
{

    const FILE_NAME = 'file_name';

    const TYPE_PROFILE = 'type_profile';

    const MAIN_PROFILE = 'main_profile';

    const VERSION_PROFILE = 'version_profile';

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
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;
    /**
     * @var \Bluecom\PaymentFee\Model\ResourceModel\PaymentFee
     */
    protected $modelPaymentFee;
    /**
     * @var \Riki\SubscriptionCourse\Model\Course
     */
    protected $subscriptionCourse;
    /**
     * @var SubscriptionBeforeImport
     */
    protected $subscriptionBeforeImport;
    /**
     * @var \Riki\Subscription\Model\Version\Version
     */
    protected $subscriptionVersion;

    /**
     * SubscriptionImport constructor.
     * @param \Riki\Subscription\Model\Profile\ProfileImport $modelSubscriptionProfile
     * @param \Magento\Framework\File\Csv $reader
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Riki\SubscriptionCourse\Model\Course $subscriptionCourse
     * @param \Bluecom\PaymentFee\Model\ResourceModel\PaymentFee $modelPaymentFee
     * @param SubscriptionBeforeImport $subscriptionBeforeImport
     * @param \Riki\Subscription\Model\Version\Version $subscriptionVersion
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface
     */
    public function __construct(
        \Riki\Subscription\Model\Profile\ProfileImport $modelSubscriptionProfile,
        \Magento\Framework\File\Csv $reader,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Riki\SubscriptionCourse\Model\Course $subscriptionCourse,
        \Bluecom\PaymentFee\Model\ResourceModel\PaymentFee $modelPaymentFee,
        \Riki\Subscription\Command\SubscriptionBeforeImport $subscriptionBeforeImport,
        \Riki\Subscription\Model\Version\Version $subscriptionVersion,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface
    )
    {
        $this->_modelSubscriptionProfile = $modelSubscriptionProfile;
        $this->_readerCSV = $reader;
        $this->_time = $timezoneInterface;
        $this->_customerFactory = $customerFactory;
        $this->modelPaymentFee = $modelPaymentFee;
        $this->subscriptionCourse = $subscriptionCourse;
        $this->subscriptionBeforeImport = $subscriptionBeforeImport;
        $this->subscriptionVersion = $subscriptionVersion;

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
                self::TYPE_PROFILE,
                InputArgument::REQUIRED,
                'Check type profile'
            )
        ];
        $this->setName('riki:subscription:import-file')
            ->setDescription('A cli Import Subscription Profile')
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

        $typeProfile = $input->getArgument(self::TYPE_PROFILE);

        //set mod import data;
        $this->subscriptionBeforeImport->isModeImport = true;
        $this->subscriptionBeforeImport->currentTypeProfile = $typeProfile;


        if (!$typeProfile) {
            $typeProfile = self::MAIN_PROFILE;
        }

        if ($fileName != "") {
            try {

                list($dataResult, $dataForValidated) = $this->subscriptionBeforeImport->prepareData($fileName);
                $dataOldProfilesWithKey = $dataForValidated['profile_id_data'];

                $row = 2;

                $iBulkData = 10000;
                $iCount = 0;
                $iCountBulk = 0;

                $dataMultipleImport = [];
                $outputMessage = [];

                $aIdOldProfileVersions = [];

                foreach ($dataResult as $data) {

                    // convert Data
                    $dataImport = $this->subscriptionBeforeImport->convertDataImport($data);

                    //validate data
                    $dataBeforeImport = $this->subscriptionBeforeImport->validateData($dataImport, $typeProfile, $row);
                    $dataImport = $dataBeforeImport['dataImport'];

                    $errors = $dataBeforeImport['error'];
                    if (count($errors) > 0) {
                        $output->writeln("--------------------------------------------------------------------------");
                        $output->writeln("[Row $row] Validate error!\n");
                        $output->writeln(implode("\n", $errors) . "\n");
                    } else {
                        try {

                            if ($iCount >= $iBulkData) {

                                $iInsertedId = $this->subscriptionBeforeImport->insertMultipleRecord('subscription_profile', $dataMultipleImport);
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
                            $aIdOldProfileVersions[] = $dataImport['old_profile_id'];
                            $outputMessage[] =  "[Row $row] Subscription profile id:" . $dataImport['old_profile_id'] . " was import successfully!\n";

                            $iCount++;

                            //delete version old
                            $dataDelete = $this->subscriptionBeforeImport->arrVersionDelete;
                            if(is_array($dataDelete)&& count ($dataDelete)>0){
                                $this->subscriptionBeforeImport->cleanDataUpdateVersionProfile($dataDelete);
                            }

                        } catch (\Exception $e) {
                            $output->writeln($e->getMessage());
                            exit();
                        }
                    }
                    $row++;
                }


                if (!empty($dataMultipleImport)) {

                    $iInsertedId = $this->subscriptionBeforeImport->insertMultipleRecord('subscription_profile', $dataMultipleImport);

                    if ($iInsertedId == count($dataMultipleImport)) {
                        $output->writeln(implode("\n", $outputMessage) . "\n");
                    } else {
                        $output->writeln("Missing record at bulk data " . $iBulkData . "\n");
                    }
                    $iCountBulk += 1;
                }

            } catch (\Exception $e) {
                $output->writeln($e->getMessage());
                exit();
            }
        }


        //insert version for profile
        if ($typeProfile == self::VERSION_PROFILE) {

            $iBulkData = 10000;
            $iCountVersion = 0;
            $iCountBulkVersion = 0;

            $dataMultipleVersionImport = [];
            $outputMessageVersion = [];

            $dataOldVersionProfiles = $this->subscriptionBeforeImport->getOldProfileId($aIdOldProfileVersions);
            $dataOldVersionProfilesWithKey = [];
            if(is_array($dataOldVersionProfiles)){
                foreach ($dataOldVersionProfiles as $dataOldProfile) {
                    if (isset($dataOldProfile['old_profile_id'])  && isset($dataOldProfilesWithKey[$dataOldProfile['old_profile_id']])) {
                        $mainProfile = reset($dataOldProfilesWithKey[$dataOldProfile['old_profile_id']]);
                        $dataOldVersionProfilesWithKey[$dataOldProfile['old_profile_id']] = $dataOldProfile;
                        $dataOldVersionProfilesWithKey[$dataOldProfile['old_profile_id']]['main_profile'] = $mainProfile;
                    }
                }
            }

            unset($dataOldVersionProfiles);

            foreach ($dataOldVersionProfilesWithKey as $iOldProfileId => $dataVersionProfile) {
                $isStatus = 1;
                $arrDataVersion = array(
                    'rollback_id' => $dataVersionProfile['main_profile']['profile_id'],
                    'moved_to' => $dataVersionProfile['profile_id'],
                    'start_time' => $dataVersionProfile['next_delivery_date'],
                    'is_rollback' => 0,
                    'status' => $dataVersionProfile['main_profile']['status']? $isStatus : 0
                );

                if ($iCountVersion >= $iBulkData) {

                    $iInsertedId = $this->subscriptionBeforeImport->insertMultipleRecord('subscription_profile_version', $dataMultipleVersionImport);
                    if ($iInsertedId == count($dataMultipleVersionImport)) {
                        $output->writeln(implode("\n", $outputMessageVersion) . "\n");
                    } else {
                        $output->writeln("Missing record at bulk data version" . $iCountBulkVersion . "\n");
                    }

                    $dataMultipleVersionImport = [];
                    $outputMessageVersion = [];

                    $iCountVersion = 0;
                    $iCountBulkVersion += 1;
                }

                $iCountVersion += 1;

                $dataMultipleVersionImport[] = $arrDataVersion;
                $outputMessageVersion[] = "Profile Version Id:" . $iOldProfileId . " was created successfully!\n";

                //update next order date and next delivery date

                $allOrderTimeNotUpdate = $this->subscriptionBeforeImport->arrIdProfileUpdate;
                $updateOrderTIme = null;
                if (isset($allOrderTimeNotUpdate[$dataVersionProfile['main_profile']['profile_id']])){
                    $updateOrderTIme = $allOrderTimeNotUpdate[$dataVersionProfile['main_profile']['profile_id']];
                }

                //main profile
                $this->subscriptionBeforeImport->updateDeliveryDateMainProfile($dataVersionProfile,$updateOrderTIme);
                $output->writeln("Update Data Profile Version Id:" . $iOldProfileId . " successfully!\n");

            }

            if (!empty($dataMultipleVersionImport)) {

                $iInsertedId = $this->subscriptionBeforeImport->insertMultipleRecord('subscription_profile_version', $dataMultipleVersionImport);

                if ($iInsertedId == count($dataMultipleVersionImport)) {
                    $output->writeln(implode("\n", $outputMessageVersion) . "\n");
                } else {
                    $output->writeln("Missing record at bulk data version" . $iBulkData . "\n");
                }
                $iCountBulkVersion += 1;
            }

        }

        $timeElapsedSecs = microtime(true) - $flagTime;
        echo "Script run time :" . ($timeElapsedSecs) . "\n";
    }
}