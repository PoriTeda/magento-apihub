<?php

namespace Riki\ThirdPartyImportExport\Helper\ExportBi;

class EnqueteHelper extends \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\ExportHelper
{
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var \Riki\Questionnaire\Model\ResourceModel\Questionnaire\CollectionFactory
     */
    protected $_questioinNaireCollectionFactory;

    /**
     * @var \Riki\Questionnaire\Model\ResourceModel\Question\CollectionFactory
     */
    protected $_questionCollectionFactory;

    /**
     * @var \Riki\Questionnaire\Model\Questionnaire
     */
    protected $_questionNaire;

    /**
     * @var \Riki\Questionnaire\Model\ResourceModel\Choice\CollectionFactory
     */
    protected $_choiceCollectionFactory;

    /**
     * @var \Riki\Questionnaire\Model\ResourceModel\Answers\CollectionFactory
     */
    protected $_answerCollectionFactory;

    /**
     * @var \Riki\Questionnaire\Model\ResourceModel\Reply\CollectionFactory
     */
    protected $_replyCollectionFactory;

    /*list customer consumber db id data*/
    protected $_consumerDb = [];

    /*last time that cron is run*/
    protected $_timeLastRunCron;

    /*exported date*/
    protected $_exportedDate;

    /**
     * Default connection
     *
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_connection;

    /**
     * EnqueteHelper constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Riki\Questionnaire\Model\ResourceModel\Questionnaire\CollectionFactory $questionNaireCollectionFactory
     * @param \Riki\Questionnaire\Model\ResourceModel\Question\CollectionFactory $questionCollectionFactory
     * @param \Riki\Questionnaire\Model\Questionnaire $questionNaire
     * @param \Riki\Questionnaire\Model\ResourceModel\Choice\CollectionFactory $choiceCollectionFactory
     * @param \Riki\Questionnaire\Model\ResourceModel\Answers\CollectionFactory $answerCollectionFactory
     * @param \Riki\Questionnaire\Model\ResourceModel\Reply\CollectionFactory $replyCollectionFactory
     * @param GlobalHelper\SftpExportHelper $sftpHelper
     * @param GlobalHelper\FileExportHelper $fileHelper
     * @param GlobalHelper\ConfigExportHelper $configHelper
     * @param GlobalHelper\EmailExportHelper $emailHelper
     * @param GlobalHelper\DateTimeColumnsHelper $dateTimeColumnsHelper
     * @param \Riki\Sales\Helper\ConnectionHelper $connectionHelper
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Riki\Questionnaire\Model\ResourceModel\Questionnaire\CollectionFactory $questionNaireCollectionFactory,
        \Riki\Questionnaire\Model\ResourceModel\Question\CollectionFactory $questionCollectionFactory,
        \Riki\Questionnaire\Model\Questionnaire $questionNaire,
        \Riki\Questionnaire\Model\ResourceModel\Choice\CollectionFactory $choiceCollectionFactory,
        \Riki\Questionnaire\Model\ResourceModel\Answers\CollectionFactory $answerCollectionFactory,
        \Riki\Questionnaire\Model\ResourceModel\Reply\CollectionFactory $replyCollectionFactory,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\SftpExportHelper $sftpHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\FileExportHelper $fileHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\ConfigExportHelper $configHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\EmailExportHelper $emailHelper,
        \Riki\ThirdPartyImportExport\Helper\ExportBi\GlobalHelper\DateTimeColumnsHelper $dateTimeColumnsHelper,
        \Riki\Sales\Helper\ConnectionHelper $connectionHelper
    ) {
        parent::__construct($context, $dateTime, $timezone, $resourceConfig, $sftpHelper, $fileHelper, $configHelper, $emailHelper, $dateTimeColumnsHelper, $connectionHelper);
        $this->_customerFactory = $customerFactory;
        $this->_questioinNaireCollectionFactory = $questionNaireCollectionFactory;
        $this->_questionCollectionFactory = $questionCollectionFactory;
        $this->_questionNaire = $questionNaire;
        $this->_choiceCollectionFactory = $choiceCollectionFactory;
        $this->_answerCollectionFactory = $answerCollectionFactory;
        $this->_replyCollectionFactory = $replyCollectionFactory;
        $this->_connection = $connectionHelper->getDefaultConnection();
    }

    /**
     * Export process
     */
    public function exportProcess()
    {
        /*get last time that this cron to run*/
        $this->_timeLastRunCron = $this->getLastRunToCron();
        /*export date*/
        $this->_exportedDate = $this->_timezone->date()->format('YmdHis');
        /*export main process*/
        $this->export();
        /* set last time to run */
        $this->setLastRunToCron();

        /*move export folder to ftp*/
        $this->_sftpHelper->moveFileToFtp($this->_pathTmp, $this->_path, $this->getSFTPPathExport(), $this->getReportPathExport());

        /*send email notify*/
        $this->sentNotificationEmail();

        /*delete lock file*/
        $this->deleteLockFile();
    }

    /**
     * @return bool|void
     */
    public function export()
    {
        $this->exportEnqueteReply();
        $this->exportEnqueteAnswerHead();
        $this->exportEnqueteReplyInput();
        $this->exportEnqueteChoice();
        $this->exportEnquete();
        $this->exportEnqueteQuestion();
    }

    /**
     * Export Enquete reply choice when to created new order
     *
     * Export: enquete_reply_choice-<date>.csv
     */
    public function exportEnqueteReply()
    {
        // HEADER FILE CSV EXPORT
        $arrayExport[] = [
            // TABLE: riki_enquete_answer_reply || All columns
            'enquete_reply.reply_id',
            'enquete_reply.answer_id',
            'enquete_reply.question_id',
            'enquete_reply.choice_id',
            'enquete_reply.content',

            // TABLE: riki_enquete_answer || All columns || PREFIX NAME: enquete_answer_
            'enquete_reply.enquete_answer_answer_id',
            'enquete_reply.enquete_answer_enquete_id',
            'enquete_reply.enquete_answer_customer_id',
            'enquete_reply.enquete_answer_order_id',
            'enquete_reply.enquete_answer_entity_id',
            'enquete_reply.enquete_answer_entity_type',
            'enquete_reply.enquete_answer_created_at',
            'enquete_reply.enquete_answer_updated_at',

            // TABLE: riki_enquete || All columns || PREFIX NAME: enquete_
            'enquete_reply.enquete_enquete_id',
            'enquete_reply.enquete_enquete_type',
            'enquete_reply.enquete_code',
            'enquete_reply.enquete_name',
            'enquete_reply.enquete_start_date',
            'enquete_reply.enquete_end_date',
            'enquete_reply.enquete_priority',
            'enquete_reply.enquete_is_enabled',
            'enquete_reply.enquete_linked_product_sku',
            'enquete_reply.enquete_visible_on_checkout',
            'enquete_reply.enquete_is_available_backend_only',
            'enquete_reply.enquete_created_at',
            'enquete_reply.enquete_updated_at',
            'enquete_reply.enquete_visible_on_order_success_page',

            // TABLE: riki_enquete_question || All columns || PREFIX NAME: enquete_question_
            'enquete_reply.enquete_question_question_id',
            'enquete_reply.enquete_question_enquete_id',
            'enquete_reply.enquete_question_is_required',
            'enquete_reply.enquete_question_type',
            'enquete_reply.enquete_question_title',
            'enquete_reply.enquete_question_sort_order',
            'enquete_reply.enquete_question_enquete_question_no',

            // TABLE: riki_enquete_question_choice || All columns || PREFIX NAME: question_choice_
            'enquete_reply.question_choice_choice_id',
            'enquete_reply.question_choice_question_id',
            'enquete_reply.question_choice_label',
            'enquete_reply.question_choice_sort_order',
            'enquete_reply.question_choice_parent_choice_id',
            'enquete_reply.question_choice_enquete_choices_no',

            // GET: customer_consumer_db_id
            'enquete_reply.customer_consumer_db_id'
        ];

        $collection = $this->_replyCollectionFactory->create();

        $collection->getSelect()
            ->joinInner(
                ['riki_en_ans' => 'riki_enquete_answer'],
                'riki_en_ans.answer_id = main_table.answer_id',
                [
                    'riki_en_ans_answer_id' => 'riki_en_ans.answer_id',
                    'riki_en_ans_enquete_id' => 'riki_en_ans.enquete_id',
                    'riki_en_ans_customer_id' => 'riki_en_ans.customer_id',
                    'riki_en_ans_entity_id' => 'riki_en_ans.entity_id',
                    'riki_en_ans_entity_type' => 'riki_en_ans.entity_type',
                    'riki_en_ans_created_at' => 'riki_en_ans.created_at',
                    'riki_en_ans_updated_at' => 'riki_en_ans.updated_at'
                ]
            );

        $collection->getSelect()
            ->joinInner(
                ['riki_en' => 'riki_enquete'],
                'riki_en.enquete_id = riki_en_ans.enquete_id',
                [
                    'riki_en_enquete_id' => 'riki_en.enquete_id',
                    'riki_en_enquete_type' => 'riki_en.enquete_type',
                    'riki_en_code' => 'riki_en.code',
                    'riki_en_name' => 'riki_en.name',
                    'riki_en_start_date' => 'riki_en.start_date',
                    'riki_en_end_date' => 'riki_en.end_date',
                    'riki_en_priority' => 'riki_en.priority',
                    'riki_en_is_enabled' => 'riki_en.is_enabled',
                    'riki_en_linked_product_sku' => 'riki_en.linked_product_sku',
                    'riki_en_visible_on_checkout' => 'riki_en.visible_on_checkout',
                    'riki_en_is_available_backend_only' => 'riki_en.is_available_backend_only',
                    'riki_en_created_at' => 'riki_en.created_at',
                    'riki_en_updated_at' => 'riki_en.updated_at',
                    'riki_en_visible_on_order_success_page' => 'riki_en.visible_on_order_success_page'
                ]
            );

        $collection->getSelect()
            ->joinInner(
                ['riki_en_ques' => 'riki_enquete_question'],
                'riki_en_ques.question_id = main_table.question_id',
                [
                    'riki_en_ques_question_id' => 'riki_en_ques.question_id',
                    'riki_en_ques_enquete_id' => 'riki_en_ques.enquete_id',
                    'riki_en_ques_is_required' => 'riki_en_ques.is_required',
                    'riki_en_ques_type' => 'riki_en_ques.type',
                    'riki_en_ques_title' => 'riki_en_ques.title',
                    'riki_en_ques_sort_order' => 'riki_en_ques.sort_order',
                    'riki_en_ques_enquete_question_no' => 'riki_en_ques.legacy_enquete_question_no'
                ]
            );

        $collection->getSelect()
            ->joinInner(['riki_en_ques_choice' => 'riki_enquete_question_choice'], 'riki_en_ques_choice.choice_id = main_table.choice_id',
                [
                    'riki_en_ques_choice_choice_id' => 'riki_en_ques_choice.choice_id',
                    'riki_en_ques_choice_question_id' => 'riki_en_ques_choice.question_id',
                    'riki_en_ques_choice_label' => 'riki_en_ques_choice.label',
                    'riki_en_ques_choice_sort_order' => 'riki_en_ques_choice.sort_order',
                    'riki_en_ques_choice_parent_choice_id' => 'riki_en_ques_choice.parent_choice_id',
                    'riki_en_ques_choice_enquete_choices_no' => 'riki_en_ques_choice.legacy_enquete_choices_no'
                ]
            );
        $collection->addFieldToFilter('main_table.choice_id', ['neq' => '']);

        if ($this->_timeLastRunCron) {
            $collection->addFieldToFilter('riki_en_ans.updated_at', [
                'gteq' => $this->_timeLastRunCron
            ]);
        }
        $countTotal = $collection->getSize();
        if (!empty($countTotal)) {
            $i = 1;
            foreach ($collection->getItems() as $enqueteReply) {
                $customerId = $enqueteReply->getData('riki_en_ans_customer_id');
                $consumerDb = null;
                if (!empty($customerId)) {
                    $consumerDb = $this->getConsumerIdByUserId($customerId);
                }

                /*enquete created at*/
                $enqueteCreated = $enqueteReply->getData('riki_en_created_at');
                if (!empty($enqueteCreated)) {
                    /*convert to config timezone*/
                    $enqueteCreated = $this->convertToConfigTimezone($enqueteCreated);
                }

                /*enquete updated at*/
                $enqueteUpdated = $enqueteReply->getData('riki_en_updated_at');
                if (!empty($enqueteUpdated)) {
                    /*convert to config timezone*/
                    $enqueteUpdated = $this->convertToConfigTimezone($enqueteUpdated);
                }

                /*answer created at*/
                $answerCreated = $enqueteReply->getData('riki_en_ans_created_at');
                if (!empty($answerCreated)) {
                    /*convert to config timezone*/
                    $answerCreated = $this->convertToConfigTimezone($answerCreated);
                }

                /*answer updated at*/
                $answerUpdated = $enqueteReply->getData('riki_en_ans_updated_at');
                if (!empty($answerUpdated)) {
                    /*convert to config timezone*/
                    $answerUpdated = $this->convertToConfigTimezone($answerUpdated);
                }
                $rikiEnAnsOrderId = $enqueteReply->getData('riki_en_ans_entity_type') == 0 ?
                    $enqueteReply->getData('riki_en_ans_entity_id') : '';
                $arrayExport[$i] = [
                    $enqueteReply->getData('reply_id'),
                    $enqueteReply->getData('answer_id'),
                    $enqueteReply->getData('question_id'),
                    $enqueteReply->getData('choice_id'),
                    $enqueteReply->getData('content'),

                    $enqueteReply->getData('riki_en_ans_answer_id'),
                    $enqueteReply->getData('riki_en_ans_enquete_id'),
                    $enqueteReply->getData('riki_en_ans_customer_id'),
                    $rikiEnAnsOrderId,
                    $enqueteReply->getData('riki_en_ans_entity_id'),
                    $enqueteReply->getData('riki_en_ans_entity_type'),
                    $answerCreated,
                    $answerUpdated,

                    $enqueteReply->getData('riki_en_enquete_id'),
                    $enqueteReply->getData('riki_en_enquete_type'),
                    $enqueteReply->getData('riki_en_code'),
                    $enqueteReply->getData('riki_en_name'),
                    $enqueteReply->getData('riki_en_start_date'),
                    $enqueteReply->getData('riki_en_end_date'),
                    $enqueteReply->getData('riki_en_priority'),
                    $enqueteReply->getData('riki_en_is_enabled'),
                    $enqueteReply->getData('riki_en_linked_product_sku'),
                    $enqueteReply->getData('riki_en_visible_on_checkout'),
                    $enqueteReply->getData('riki_en_is_available_backend_only'),
                    $enqueteCreated,
                    $enqueteUpdated,
                    $enqueteReply->getData('riki_en_visible_on_order_success_page'),

                    $enqueteReply->getData('riki_en_ques_question_id'),
                    $enqueteReply->getData('riki_en_ques_enquete_id'),
                    $enqueteReply->getData('riki_en_ques_is_required'),
                    $enqueteReply->getData('riki_en_ques_type'),
                    $enqueteReply->getData('riki_en_ques_title'),
                    $enqueteReply->getData('riki_en_ques_sort_order'),
                    $enqueteReply->getData('riki_en_ques_enquete_question_no'),

                    $enqueteReply->getData('riki_en_ques_choice_choice_id'),
                    $enqueteReply->getData('riki_en_ques_choice_question_id'),
                    $enqueteReply->getData('riki_en_ques_choice_label'),
                    $enqueteReply->getData('riki_en_ques_choice_sort_order'),
                    $enqueteReply->getData('riki_en_ques_choice_parent_choice_id'),
                    $enqueteReply->getData('riki_en_ques_choice_enquete_choices_no'),
                    $consumerDb
                ];
                $i++;
            }
        }

        /*export file name*/
        $exportFileName = 'enquete_reply_choice-'.$this->_exportedDate.'.csv';

        /*create local file*/
        $this->createLocalFile([
            $exportFileName => $arrayExport
        ]);
    }

    /**
     * Export enquete answer header when to created new order
     *
     * Export enquete_answer_header-<date>.csv
     */
    public function exportEnqueteAnswerHead()
    {
        // HEADER FILE CSV COLUMN
        $arrayExport[] = [
            // TABLE: riki_enquete_answer || All columns
            'enquete_answer_header.answer_id',
            'enquete_answer_header.enquete_id',
            'enquete_answer_header.customer_id',
            'enquete_answer_header.order_id',
            'enquete_answer_header.entity_id',
            'enquete_answer_header.entity_type',
            'enquete_answer_header.created_at',
            'enquete_answer_header.updated_at',
            'enquete_answer_header.customer_consumer_db_id',

            // TABLE: riki_enquete || All columns || PREFIX NAME: enquete_
            'enquete_answer_header.enquete_enquete_id',
            'enquete_answer_header.enquete_enquete_type',
            'enquete_answer_header.enquete_code',
            'enquete_answer_header.enquete_name',
            'enquete_answer_header.enquete_start_date',
            'enquete_answer_header.enquete_end_date',
            'enquete_answer_header.enquete_priority',
            'enquete_answer_header.enquete_is_enabled',
            'enquete_answer_header.enquete_linked_product_sku',
            'enquete_answer_header.enquete_visible_on_checkout',
            'enquete_answer_header.enquete_is_available_backend_only',
            'enquete_answer_header.enquete_created_at',
            'enquete_answer_header.enquete_updated_at',
            'enquete_answer_header.enquete_visible_on_order_success_page',

            // TABLE: sales_order || COLUMN: increment_id || CSV COLUMN: enquete_answer_header.order_increment_id
            'enquete_answer_header.order_increment_id'
        ];

        $collection = $this->_answerCollectionFactory->create();

        $collection->getSelect()
            ->joinInner(
                ['en_que' => 'riki_enquete'],
                'en_que.enquete_id = main_table.enquete_id',
                [
                    'enquete_enquete_id' => 'en_que.enquete_id',
                    'enquete_enquete_type' => 'en_que.enquete_type',
                    'enquete_code' => 'en_que.code',
                    'enquete_name' => 'en_que.name',
                    'enquete_start_date' => 'en_que.start_date',
                    'enquete_end_date' => 'en_que.end_date',
                    'enquete_priority' => 'en_que.priority',
                    'enquete_is_enabled' => 'en_que.is_enabled',
                    'enquete_linked_product_sku' => 'en_que.linked_product_sku',
                    'enquete_visible_on_checkout' => 'en_que.visible_on_checkout',
                    'enquete_is_available_backend_only' => 'en_que.is_available_backend_only',
                    'enquete_created_at' => 'en_que.created_at',
                    'enquete_updated_at' => 'en_que.updated_at',
                    'enquete_visible_on_order_success_page' => 'en_que.visible_on_order_success_page'
                ]
            );

        $collection->getSelect()
            ->joinLeft(
                ['sale' => 'sales_order'],
                'sale.entity_id = main_table.entity_id',
                [
                    'order_increment_id' => 'sale.increment_id'
                ]
            );

        if ($this->_timeLastRunCron) {
            $collection->addFieldToFilter('main_table.updated_at', ['gteq' => $this->_timeLastRunCron]);
        }

        $countTotal = $collection->getSize();
        if (!empty($countTotal)) {
            $i = 1;
            foreach ($collection->getItems() as $answer) {
                $consumerDb = null;
                $customerId = $answer->getData('customer_id');
                if (!empty($customerId)) {
                    $consumerDb = $this->getConsumerIdByUserId($customerId);
                }

                /*enquete created at*/
                $enqueteCreated = $answer->getData('enquete_created_at');
                if (!empty($enqueteCreated)) {
                    /*convert to config timezone*/
                    $enqueteCreated = $this->convertToConfigTimezone($enqueteCreated);
                }

                /*enquete updated at*/
                $enqueteUpdated = $answer->getData('enquete_updated_at');
                if (!empty($enqueteUpdated)) {
                    /*convert to config timezone*/
                    $enqueteUpdated = $this->convertToConfigTimezone($enqueteUpdated);
                }

                /*answer created at*/
                $answerCreated = $answer->getData('created_at');
                if (!empty($answerCreated)) {
                    /*convert to config timezone*/
                    $answerCreated = $this->convertToConfigTimezone($answerCreated);
                }

                /*answer updated at*/
                $answerUpdated = $answer->getData('updated_at');
                if (!empty($answerUpdated)) {
                    /*convert to config timezone*/
                    $answerUpdated = $this->convertToConfigTimezone($answerUpdated);
                }
                $answerOrderId = $answer->getData('entity_type') == 0 ? $answer->getData('entity_id') : '';
                $orderIncrementId = $answer->getData('entity_type') == 0 ? $answer->getData('order_increment_id') : '';
                $arrayExport[$i] = [
                    $answer->getData('answer_id'),
                    $answer->getData('enquete_id'),
                    $answer->getData('customer_id'),
                    $answerOrderId,
                    $answer->getData('entity_id'),
                    $answer->getData('entity_type'),
                    $answerCreated,
                    $answerUpdated,
                    $consumerDb,
                    $answer->getData('enquete_enquete_id'),
                    $answer->getData('enquete_enquete_type'),
                    $answer->getData('enquete_code'),
                    $answer->getData('enquete_name'),
                    $answer->getData('enquete_start_date'),
                    $answer->getData('enquete_end_date'),
                    $answer->getData('enquete_priority'),
                    $answer->getData('enquete_is_enabled'),
                    $answer->getData('enquete_linked_product_sku'),
                    $answer->getData('enquete_visible_on_checkout'),
                    $answer->getData('enquete_is_available_backend_only'),
                    $enqueteCreated,
                    $enqueteUpdated,
                    $answer->getData('enquete_visible_on_order_success_page'),
                    $orderIncrementId
                ];
                $i++;
            }
        }

        /*export file name*/
        $exportFileName = 'enquete_answer_header-'.$this->_exportedDate.'.csv';

        /*create local file*/
        $this->createLocalFile([
            $exportFileName => $arrayExport
        ]);
    }

    /**
     * Export Enquete reply input when to created new order
     *
     * Export: enquete_reply_input-<date>.csv
     */
    public function exportEnqueteReplyInput()
    {
        // HEADER FILE CSV EXPORT
        $arrayExport[] = [
            // TABLE: riki_enquete_answer_reply || All columns
            'enquete_reply.reply_id',
            'enquete_reply.answer_id',
            'enquete_reply.question_id',
            'enquete_reply.choice_id',
            'enquete_reply.content',

            // TABLE: riki_enquete_answer || All columns || PREFIX NAME: enquete_answer_
            'enquete_reply.enquete_answer_answer_id',
            'enquete_reply.enquete_answer_enquete_id',
            'enquete_reply.enquete_answer_customer_id',
            'enquete_reply.enquete_answer_order_id',
            'enquete_reply.enquete_answer_entity_id',
            'enquete_reply.enquete_answer_entity_type',
            'enquete_reply.enquete_answer_created_at',
            'enquete_reply.enquete_answer_updated_at',

            // TABLE: riki_enquete || All columns || PREFIX NAME: enquete_
            'enquete_reply.enquete_enquete_id',
            'enquete_reply.enquete_enquete_type',
            'enquete_reply.enquete_code',
            'enquete_reply.enquete_name',
            'enquete_reply.enquete_start_date',
            'enquete_reply.enquete_end_date',
            'enquete_reply.enquete_priority',
            'enquete_reply.enquete_is_enabled',
            'enquete_reply.enquete_linked_product_sku',
            'enquete_reply.enquete_visible_on_checkout',
            'enquete_reply.enquete_is_available_backend_only',
            'enquete_reply.enquete_created_at',
            'enquete_reply.enquete_updated_at',
            'enquete_reply.enquete_visible_on_order_success_page',

            // TABLE: riki_enquete_question || All columns || PREFIX NAME: enquete_question_
            'enquete_reply.enquete_question_question_id',
            'enquete_reply.enquete_question_enquete_id',
            'enquete_reply.enquete_question_is_required',
            'enquete_reply.enquete_question_type',
            'enquete_reply.enquete_question_title',
            'enquete_reply.enquete_question_sort_order',
            'enquete_reply.enquete_question_enquete_question_no',

            // GET: customer_consumer_db_id
            'enquete_reply.customer_consumer_db_id'
        ];

        $collection = $this->_replyCollectionFactory->create();

        $collection->getSelect()
            ->joinInner(
                ['riki_en_ans' => 'riki_enquete_answer'],
                'riki_en_ans.answer_id = main_table.answer_id',
                [
                    'riki_en_ans_answer_id' => 'riki_en_ans.answer_id',
                    'riki_en_ans_enquete_id' => 'riki_en_ans.enquete_id',
                    'riki_en_ans_customer_id' => 'riki_en_ans.customer_id',
                    'riki_en_ans_entity_id' => 'riki_en_ans.entity_id',
                    'riki_en_ans_entity_type' => 'riki_en_ans.entity_type',
                    'riki_en_ans_created_at' => 'riki_en_ans.created_at',
                    'riki_en_ans_updated_at' => 'riki_en_ans.updated_at'
                ]
            );

        $collection->getSelect()
            ->joinInner(
                ['riki_en' => 'riki_enquete'],
                'riki_en.enquete_id = riki_en_ans.enquete_id',
                [
                    'riki_en_enquete_id' => 'riki_en.enquete_id',
                    'riki_en_enquete_type' => 'riki_en.enquete_type',
                    'riki_en_code' => 'riki_en.code',
                    'riki_en_name' => 'riki_en.name',
                    'riki_en_start_date' => 'riki_en.start_date',
                    'riki_en_end_date' => 'riki_en.end_date',
                    'riki_en_priority' => 'riki_en.priority',
                    'riki_en_is_enabled' => 'riki_en.is_enabled',
                    'riki_en_linked_product_sku' => 'riki_en.linked_product_sku',
                    'riki_en_visible_on_checkout' => 'riki_en.visible_on_checkout',
                    'riki_en_is_available_backend_only' => 'riki_en.is_available_backend_only',
                    'riki_en_created_at' => 'riki_en.created_at',
                    'riki_en_updated_at' => 'riki_en.updated_at',
                    'riki_en_visible_on_order_success_page' => 'riki_en.visible_on_order_success_page'
                ]
            );

        $collection->getSelect()
            ->joinInner(
                ['riki_en_ques' => 'riki_enquete_question'],
                'riki_en_ques.question_id = main_table.question_id',
                [
                    'riki_en_ques_question_id' => 'riki_en_ques.question_id',
                    'riki_en_ques_enquete_id' => 'riki_en_ques.enquete_id',
                    'riki_en_ques_is_required' => 'riki_en_ques.is_required',
                    'riki_en_ques_type' => 'riki_en_ques.type',
                    'riki_en_ques_title' => 'riki_en_ques.title',
                    'riki_en_ques_sort_order' => 'riki_en_ques.sort_order',
                    'riki_en_ques_enquete_question_no' => 'riki_en_ques.legacy_enquete_question_no'
                ]
            );

        $collection->addFieldToFilter('main_table.content', ['neq' => '']);

        if ($this->_timeLastRunCron) {
            $collection->addFieldToFilter('riki_en_ans.updated_at', [
                'gteq' => $this->_timeLastRunCron
            ]);
        }

        $countTotal = $collection->getSize();
        if (!empty($countTotal)) {
            $i = 1;
            foreach ($collection->getItems() as $enqueteReply) {
                $customerId = $enqueteReply->getData('riki_en_ans_customer_id');
                $consumerDb = null;
                if (!empty($customerId)) {
                    $consumerDb = $this->getConsumerIdByUserId($customerId);
                }

                /*enquete created at*/
                $enqueteCreated = $enqueteReply->getData('riki_en_created_at');
                if (!empty($enqueteCreated)) {
                    /*convert to config timezone*/
                    $enqueteCreated = $this->convertToConfigTimezone($enqueteCreated);
                }

                /*enquete updated at*/
                $enqueteUpdated = $enqueteReply->getData('riki_en_updated_at');
                if (!empty($enqueteUpdated)) {
                    /*convert to config timezone*/
                    $enqueteUpdated = $this->convertToConfigTimezone($enqueteUpdated);
                }

                /*answer created at*/
                $answerCreated = $enqueteReply->getData('riki_en_ans_created_at');
                if (!empty($answerCreated)) {
                    /*convert to config timezone*/
                    $answerCreated = $this->convertToConfigTimezone($answerCreated);
                }

                /*answer updated at*/
                $answerUpdated = $enqueteReply->getData('riki_en_ans_updated_at');
                if (!empty($answerUpdated)) {
                    /*convert to config timezone*/
                    $answerUpdated = $this->convertToConfigTimezone($answerUpdated);
                }

                $orderId = $enqueteReply->getData('riki_en_ans_entity_type') == 0 ?
                    $enqueteReply->getData('riki_en_ans_entity_id') : '';
                $arrayExport[$i] = [
                    $enqueteReply->getData('reply_id'),
                    $enqueteReply->getData('answer_id'),
                    $enqueteReply->getData('question_id'),
                    $enqueteReply->getData('choice_id'),
                    $enqueteReply->getData('content'),

                    $enqueteReply->getData('riki_en_ans_answer_id'),
                    $enqueteReply->getData('riki_en_ans_enquete_id'),
                    $enqueteReply->getData('riki_en_ans_customer_id'),
                    $orderId,
                    $enqueteReply->getData('riki_en_ans_entity_id'),
                    $enqueteReply->getData('riki_en_ans_entity_type'),
                    $answerCreated,
                    $answerUpdated,

                    $enqueteReply->getData('riki_en_enquete_id'),
                    $enqueteReply->getData('riki_en_enquete_type'),
                    $enqueteReply->getData('riki_en_code'),
                    $enqueteReply->getData('riki_en_name'),
                    $enqueteReply->getData('riki_en_start_date'),
                    $enqueteReply->getData('riki_en_end_date'),
                    $enqueteReply->getData('riki_en_priority'),
                    $enqueteReply->getData('riki_en_is_enabled'),
                    $enqueteReply->getData('riki_en_linked_product_sku'),
                    $enqueteReply->getData('riki_en_visible_on_checkout'),
                    $enqueteReply->getData('riki_en_is_available_backend_only'),
                    $enqueteCreated,
                    $enqueteUpdated,
                    $enqueteReply->getData('riki_en_visible_on_order_success_page'),

                    $enqueteReply->getData('riki_en_ques_question_id'),
                    $enqueteReply->getData('riki_en_ques_enquete_id'),
                    $enqueteReply->getData('riki_en_ques_is_required'),
                    $enqueteReply->getData('riki_en_ques_type'),
                    $enqueteReply->getData('riki_en_ques_title'),
                    $enqueteReply->getData('riki_en_ques_sort_order'),
                    $enqueteReply->getData('riki_en_ques_enquete_question_no'),
                    $consumerDb
                ];
                $i++;
            }
        }

        /*export file name*/
        $exportFileName = 'enquete_reply_input-'.$this->_exportedDate.'.csv';

        /*create local file*/
        $this->createLocalFile([
            $exportFileName => $arrayExport
        ]);
    }

    /**
     * Export Enquete choice
     *
     * Export enquete_choice-<date>.csv
     */
    public function exportEnqueteChoice()
    {
        // HEADER FILE CSV COLUMNS
        $arrayExport[] = [
            // TABLE: riki_enquete_question_choice || All columns
            'enquete_choice.choice_id',
            'enquete_choice.question_id',
            'enquete_choice.label',
            'enquete_choice.sort_order',
            'enquete_choice.parent_choice_id',
            'enquete_choice.enquete_choices_no',

            // TABLE: riki_enquete_question || COLUMN: enquete_choice.enquete_question_no
            'enquete_choice.enquete_question_no',

            // TABLE: riki_enquete || COLUMN: code || CSV COLUMN NAME: enquete_choice.enquete_code
            'enquete_choice.enquete_code'
        ];

        $collection = $this->_choiceCollectionFactory->create();

        $collection->getSelect()->joinInner(
            ['en_ques' => 'riki_enquete_question'],
            'en_ques.question_id = main_table.question_id',
            [
                'enquete_question_no' => 'en_ques.legacy_enquete_question_no',
            ]
        );

        $collection->getSelect()->joinInner(
            ['en_que' => 'riki_enquete'],
            'en_que.enquete_id = en_ques.enquete_id',
            [
                'enquete_code' => 'en_que.code'
            ]
        );

        if ($this->_timeLastRunCron) {
            $collection->addFieldToFilter('main_table.updated_at', [
                'gteq' => $this->_timeLastRunCron
            ]);
        }

        $countTotal = $collection->getSize();
        if (!empty($countTotal)) {
            $i  = 1;
            foreach ($collection->getItems() as $choice) {
                $arrayExport[$i] = [
                    $choice->getData('choice_id'),
                    $choice->getData('question_id'),
                    $choice->getData('label'),
                    $choice->getData('sort_order'),
                    $choice->getData('parent_choice_id'),
                    $choice->getData('legacy_enquete_choices_no'),
                    $choice->getData('enquete_question_no'),
                    $choice->getData('enquete_code')
                ];
                $i++;
            }
        }

        /*export file name*/
        $exportFileName = 'enquete_choice-'.$this->_exportedDate.'.csv';

        /*create local file*/
        $this->createLocalFile([
            $exportFileName => $arrayExport
        ]);
    }

    /**
     * Export Enquete
     *
     * Export enquete-<date>.csv
     */
    public function exportEnquete()
    {
        // HEADER FILE CSV EXPORT
        $arrayExport[] = [
            // TABLE: riki_enquete || All columns || COLUMN PREFIX: enquete.
            'enquete.enquete_id',
            'enquete.enquete_type',
            'enquete.code',
            'enquete.name',
            'enquete.start_date',
            'enquete.end_date',
            'enquete.priority',
            'enquete.is_enabled',
            'enquete.linked_product_sku',
            'enquete.visible_on_checkout',
            'enquete.is_available_backend_only',
            'enquete.created_at',
            'enquete.updated_at',
            'enquete.visible_on_order_success_page'
        ];

        $collection = $this->_questioinNaireCollectionFactory->create();

        if ($this->_timeLastRunCron) {
            $collection->addFieldToFilter('updated_at', [
                'gteq' => $this->_timeLastRunCron
            ]);
        }

        $countTotal = $collection->getSize();

        if (!empty($countTotal)) {
            $i = 1;
            foreach ($collection->getItems() as $questionnaire) {
                /*enquete created at*/
                $enqueteCreated = $questionnaire->getCreatedAt();
                if (!empty($enqueteCreated)) {
                    /*convert to config timezone*/
                    $enqueteCreated = $this->convertToConfigTimezone($enqueteCreated);
                }

                /*enquete updated at*/
                $enqueteUpdated = $questionnaire->getUpdatedAt();
                if (!empty($enqueteUpdated)) {
                    /*convert to config timezone*/
                    $enqueteUpdated = $this->convertToConfigTimezone($enqueteUpdated);
                }

                $arrayExport[$i] = [
                    $questionnaire->getData('enquete_id'),
                    $questionnaire->getData('enquete_type'),
                    $questionnaire->getData('code'),
                    $questionnaire->getData('name'),
                    $questionnaire->getData('start_date'),
                    $questionnaire->getData('end_date'),
                    $questionnaire->getData('priority'),
                    $questionnaire->getData('is_enabled'),
                    $questionnaire->getData('linked_product_sku'),
                    $questionnaire->getData('visible_on_checkout'),
                    $questionnaire->getData('is_available_backend_only'),
                    $enqueteCreated,
                    $enqueteUpdated,
                    $questionnaire->getData('visible_on_order_success_page')
                ];
                $i++;
            }
        }

        /*export file name*/
        $exportFileNameCsv = 'enquete-'.$this->_exportedDate.'.csv';

        /*create local file*/
        $this->createLocalFile([
            $exportFileNameCsv => $arrayExport
        ]);
    }

    /**
     * Export Enquete question
     *
     * Export: enquete_question-<date>.csv
     */
    public function exportEnqueteQuestion()
    {
        // HEADER COLUMN CSV
        $arrayExport[] = [
            // TABLE: riki_enquete_question || All Columns
            'enquete_question.question_id',
            'enquete_question.enquete_id',
            'enquete_question.is_required',
            'enquete_question.type',
            'enquete_question.title',
            'enquete_question.sort_order',
            'enquete_question.enquete_question_no',

            // TABLE: riki_enquete || All Columns || PREFIX NAME: enquete_
            'enquete_question.enquete_enquete_id',
            'enquete_question.enquete_enquete_type',
            'enquete_question.enquete_code',
            'enquete_question.enquete_name',
            'enquete_question.enquete_start_date',
            'enquete_question.enquete_end_date',
            'enquete_question.enquete_priority',
            'enquete_question.enquete_is_enabled',
            'enquete_question.enquete_linked_product_sku',
            'enquete_question.enquete_visible_on_checkout',
            'enquete_question.enquete_is_available_backend_only',
            'enquete_question.enquete_created_at',
            'enquete_question.enquete_updated_at',
            'enquete_question.enquete_visible_on_order_success_page'
        ];

        $collection = $this->_questionCollectionFactory->create();

        $collection->getSelect()->joinInner(
            ['en_que' => 'riki_enquete'],
            'en_que.enquete_id = main_table.enquete_id',
            'en_que.*'
        );

        if ($this->_timeLastRunCron) {
            $collection->addFieldToFilter('main_table.updated_at', [
                'gteq' => $this->_timeLastRunCron
            ]);
        }

        $countTotal = $collection->getSize();
        if (!empty($countTotal)) {
            $i = 1;
            foreach ($collection->getItems() as $question) {
                /*enquete created at*/
                $enqueteCreated = $question->getData('enquete_created_at');
                if (!empty($enqueteCreated)) {
                    /*convert to config timezone*/
                    $enqueteCreated = $this->convertToConfigTimezone($enqueteCreated);
                }

                /*enquete updated at*/
                $enqueteUpdated = $question->getData('enquete_updated_at');
                if (!empty($enqueteUpdated)) {
                    /*convert to config timezone*/
                    $enqueteUpdated = $this->convertToConfigTimezone($enqueteUpdated);
                }

                $arrayExport[$i] = [
                    $question->getData('question_id'),
                    $question->getData('enquete_id'),
                    $question->getData('is_required'),
                    $question->getData('type'),
                    $question->getData('title'),
                    $question->getData('sort_order'),
                    $question->getData('legacy_enquete_question_no'),
                    $question->getData('enquete_id'),
                    $question->getData('enquete_type'),
                    $question->getData('code'),
                    $question->getData('name'),
                    $question->getData('start_date'),
                    $question->getData('end_date'),
                    $question->getData('priority'),
                    $question->getData('is_enabled'),
                    $question->getData('linked_product_sku'),
                    $question->getData('visible_on_checkout'),
                    $question->getData('is_available_backend_only'),
                    $enqueteCreated,
                    $enqueteUpdated,
                    $question->getData('visible_on_order_success_page')
                ];
                $i++;
            }
        }

        /*export file name*/
        $exportFileName = 'enquete_question-'.$this->_exportedDate.'.csv';

        /*create local file*/
        $this->createLocalFile([
            $exportFileName => $arrayExport
        ]);
    }

    /**
     * Get consumer id by user id
     *
     * @param $customerId
     * @return \Magento\Framework\Api\AttributeInterface|mixed|null|string
     */
    public function getConsumerIdByUserId($customerId)
    {
        if (isset($this->_consumerDb[$customerId])) {
            return $this->_consumerDb[$customerId];
        }

        $consumerDb = '';
        try {
            $customer = $this->_customerFactory->create()->load($customerId);
            if (!empty($customer->getId())) {
                $consumerDb = $customer->getData('consumer_db_id');
                $this->_consumerDb[$customerId] = $consumerDb;
            }
        } catch (\Exception $e) {
            $this->_log->info($e->getMessage());
        }
        return $consumerDb;
    }

    /**
     * get last time which this cron was run
     */
    public function getLastRunToCron()
    {
        $lastTimeCronRun = '';

        try {
            /*config table name*/
            $configTable = $this->_connection->getTableName('core_config_data');

            $getLastTimeCronRun = $this->_connection->select()->from(
                $configTable,
                'value'
            )->where(
                'path = ?',
                $this->_configLastTimeRun
            )->limitPage(1, 1)->limit(1);

            $timeCronRun = $this->_connection->fetchCol($getLastTimeCronRun);

            if (!empty($timeCronRun) && !empty($timeCronRun[0])) {
                $lastTimeCronRun = $timeCronRun[0];
            }
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }

        if (empty($lastTimeCronRun)) {
            $lastTimeCronRun = parent::getLastRunToCron();
        }

        return $lastTimeCronRun;
    }

    /**
     * Set default config before export
     *
     * @param $defaultLocalPath
     * @param $configLocalPath
     * @param $configSftpPath
     * @param $configReportPath
     * @param $configLastTimeRun
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function initExport(
        $defaultLocalPath,
        $configLocalPath,
        $configSftpPath,
        $configReportPath,
        $configLastTimeRun
    ) {
        $initExport = parent::initExport(
            $defaultLocalPath,
            $configLocalPath,
            $configSftpPath,
            $configReportPath,
            $configLastTimeRun
        );
        if ($initExport) {
            /*tmp file to ensure that system do not run same mulit process at the same time*/
            $lockFile = $this->getLockFile();
            if ($this->_fileHelper->isExists($lockFile)) {
                $this->_logger->info('Please wait, system have a same process is running and haven’t finish yet.');
                throw new \Magento\Framework\Exception\LocalizedException(__('Please wait, system have a same process is running and haven’t finish yet.'));
            } else {
                $this->_fileHelper->createFile($lockFile);
            }
        }

        return $initExport;
    }

    /**
     * Get lock file
     * This lock is used to tracking that system have same process is running
     * @return string
     */
    public function getLockFile()
    {
        return $this->_path . DS . '.lock';
    }

    /**
     * Delete lock file
     */
    public function deleteLockFile()
    {
        $this->_fileHelper->deleteFile($this->getLockFile());
    }
}
