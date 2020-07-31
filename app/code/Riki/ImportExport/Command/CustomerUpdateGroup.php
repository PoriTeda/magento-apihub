<?php
/**
 * Customer.
 *
 * PHP version 7
 *
 * @category  RIKI
 *
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\ImportExport\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Magento\ImportExport\Model\Import\Adapter as ImportAdapter;

/**
 * Class Customer.
 *
 * @category  RIKI
 *
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class CustomerUpdateGroup extends Command
{
    const FILE_NAME = 'import_file';
    /**
     * Filesystem.
     *
     * @var \Magento\Framework\Filesystem Filesystem
     */
    protected $fileSystem;

    /**
     * ObjectManagerInterface.
     *
     * @var \Magento\Framework\ObjectManagerInterface ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * App State.
     *
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;

    /**
     * Customer constructor.
     *
     * @param \Magento\Framework\Filesystem             $fileSystem             Filesystem
     * @param \Magento\Framework\ObjectManagerInterface $objectManagerInterface ObjectManagerInterface
     * @param \Magento\Framework\App\State              $state
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManagerInterface,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\App\State $state
    ) {
        parent::__construct();
        $this->resourceConnection = $resourceConnection;
        $this->objectManager = $objectManagerInterface;
        $this->appState = $state;
    }

    /**
     * Set param name for CLI.
     */
    protected function configure()
    {
        $options = [
            new InputArgument(
                'type',
                InputArgument::OPTIONAL,
                'Type of update',
                'customer'
            )
        ];
        $this->setName('riki:update:customer')
            ->setDescription('Update Customer Group')
            ->setDefinition($options);
        parent::configure();
    }

    /**
     * Validate + import customer.
     *
     * @param InputInterface  $input  InputInterface
     * @param OutputInterface $output OutputInterface
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $connectionSales = $this->resourceConnection->getConnection('sales');
        $connectionDefault = $this->resourceConnection->getConnection('default');

        $type = $input->getArgument('type');

        $customerEntityTable = $this->resourceConnection->getTableName('customer_entity');

        $subscriptionProfileTable = $this->resourceConnection->getTableName('subscription_profile');

        $hanpukaiFixedTable = $this->resourceConnection->getTableName('hanpukai_fixed');

        $hanpukaiSequenseTable = $this->resourceConnection->getTableName('hanpukai_sequence');

        $customerEntityVarcharTable = $this->resourceConnection->getTableName('customer_entity_varchar');

        $selectCourseIdHanpukaiSequenseQuery = "SELECT hq.course_id FROM `$hanpukaiSequenseTable` hq";

        $selectCourseIdHanpukaiSequenResult = $connectionSales->fetchAll($selectCourseIdHanpukaiSequenseQuery);

        $selectHanpukaiFixCourseIdQuery = "SELECT hf.course_id FROM `$hanpukaiFixedTable` hf";

        $selectHanpukaiFixCourseIdQueryResult = $connectionSales->fetchAll($selectHanpukaiFixCourseIdQuery);


        $output->write("RIKI UPDATE CUSTOMER GROUP CLI\n");
            try {
                
                if($type == 2){
                    $output->write("Rungning update group to 2\n");
                    $resultSelectNotIn = array_merge($selectCourseIdHanpukaiSequenResult,$selectHanpukaiFixCourseIdQueryResult);
                    $notIn ='';
                    $lastItem = end($resultSelectNotIn);
                    foreach ( $resultSelectNotIn as $value){
                        if($value != $lastItem){
                            $notIn.=$value['course_id'].',';
                        }else{
                            $notIn.=$value['course_id'];
                        }

                    }
                    $selectCustomerSubscriptionQuery = "SELECT sp.customer_id FROM `$subscriptionProfileTable` sp WHERE sp.course_id NOT IN ($notIn)";

                    $selectCustomerSubscriptionResult = $connectionSales->fetchAll($selectCustomerSubscriptionQuery);
                    $inCustomer ='';
                    $lastItem = end($selectCustomerSubscriptionResult);
                    foreach ( $selectCustomerSubscriptionResult as $value){
                        if($value != $lastItem){
                            $inCustomer.=$value['customer_id'].',';
                        }else{
                            $inCustomer.=$value['customer_id'];
                        }

                    }
                    $updateQuery2 = "UPDATE  customer_entity e SET e.group_id = 2 WHERE e.`entity_id` IN ($inCustomer)";

                    if($connectionDefault->query($updateQuery2))
                        $output->write("Rungning update group to 2 Done\n");

                    $output->write("Rungning update group to 2 fail\n");
          
                }elseif($type ==3){
                    $output->write("Rungning update group to 2 Done\n");
                    $selectEavQuery = "SELECT cv.entity_id 
							FROM  `$customerEntityVarcharTable` cv
							WHERE cv.attribute_id IN(
									SELECT ev.attribute_id 
									FROM eav_attribute ev 
									WHERE ev.attribute_code ='membership')
									AND (cv.value LIKE '%3%' OR cv.value LIKE '%13%' OR cv.value LIKE '%14%')";
                    $selectEavResult = $connectionDefault->fetchAll($selectEavQuery);
                    $inVarchar ='';
                    $lastItem = end($selectEavResult);
                    foreach ( $selectEavResult as $value){
                        if($value != $lastItem){
                            $inVarchar.=$value['entity_id'].',';
                        }else{
                            $inVarchar.=$value['entity_id'];
                        }

                    }
                    $notInHf ='';
                    $lastItem = end($selectHanpukaiFixCourseIdQueryResult);
                    foreach ( $selectHanpukaiFixCourseIdQueryResult as $value){
                        if($value != $lastItem){
                            $notInHf.=$value['course_id'].',';
                        }else{
                            $notInHf.=$value['course_id'];
                        }

                    }

                    $notInHs ='';
                    $lastItem = end($selectCourseIdHanpukaiSequenResult);
                    foreach ( $selectCourseIdHanpukaiSequenResult as $value){
                        if($value != $lastItem){
                            $notInHs.=$value['course_id'].',';
                        }else{
                            $notInHs.=$value['course_id'];
                        }

                    }
                    $selectSubscriptionProfileCustomerIdQuery = "SELECT sp.customer_id FROM `$subscriptionProfileTable` sp";
                    if($notInHf !=''){
                        $selectSubscriptionProfileCustomerIdQuery.= " WHERE sp.course_id NOT IN ($notInHf)";
                    }
                    if($notInHs !=''){
                        $selectSubscriptionProfileCustomerIdQuery.=" AND sp.course_id NOT IN ($notInHs)";
                    }
                    $selectSubscriptionProfileCustomerIdResult = $connectionSales->fetchAll($selectSubscriptionProfileCustomerIdQuery);

                    $customerEntityIn3 ='';
                    $lastItem = end($selectSubscriptionProfileCustomerIdResult);
                    foreach ( $selectSubscriptionProfileCustomerIdResult as $value){
                        if($value != $lastItem){
                            $customerEntityIn3.=$value['customer_id'].',';
                        }else{
                            $customerEntityIn3.=$value['customer_id'];
                        }

                    }
                    $updateQuery3 = "UPDATE customer_entity e SET e.group_id =3 WHERE e.`entity_id`  IN($customerEntityIn3) AND e.`entity_id` IN($inVarchar)";

                    if($connectionDefault->query($updateQuery3))
                        $output->write("Rungning update group to 2 Done\n");
                    $output->write("Rungning update group to 2 Faile\n");
                }
            } catch (\Exception $e) {
                $output->writeln(
                    $e->getMessage()
                );

                return 1;
            }

    }

}
