<?php
/**
 * PHP version 7
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 *
 * @category  Riki_EmailMarketing
 * @package   Riki\EmailMarketing\Setup
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\EmailMarketing\Model;
use Magento\Email\Model\ResourceModel\Template\CollectionFactory;
use Magento\Email\Model\TemplateFactory;
use Magento\Framework\App\Config\Storage\WriterInterface;
/**
 * Class EmailTemplate
 *
 * @category  Riki_EmailMarketing
 * @package   Riki\EmailMarketing\Setup
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class EmailTemplate
{
    /**
     * @var
     */
    protected $collectionFactory;
    /**
     * @var TemplateFactory
     */
    protected $templateFactory;
    /**
     * @var \Riki\EmailMarketing\Helper\Data
     */
    protected $dataHelper;
    /**
     * @var WriterInterface
     */
    protected $configWriter;
    /**
     * EmailTemplate constructor.
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        TemplateFactory $templateFactory,
        \Riki\EmailMarketing\Helper\Data $dataHelper,
        WriterInterface $writer
    )
    {
        $this->collectionFactory = $collectionFactory;
        $this->templateFactory = $templateFactory;
        $this->dataHelper = $dataHelper;
        $this->configWriter = $writer;
    }

    /**
     * Install email templates for customer.
     *
     * @param null $version
     * @throws \Exception
     */
    public function setupEmailMarketingCustomer($version = null)
    {
        /*
         * Template type = 1 : text
         * Template type = 2 : html
         */
        $dataEmails = $this->dataHelper->getCustomerEmailList($version);
        $emailCollections = $this->collectionFactory->create();
        foreach($dataEmails as $newEmail) {
            //search if it exist
            $matched = false;
            foreach ($emailCollections as $email)
            {
                // if found, update template
                if (strtolower($email->getTemplateCode()) == strtolower($newEmail[0]))
                {
                    $matched = true;
                    $body = $this->dataHelper->getTxtContent
                    (
                        $newEmail[2]
                    );
                    $email->setTemplateCode($newEmail[0]);
                    $email->setTemplateType($newEmail[5]);
                    $email->setTemplateText($body);
                    $email->setTemplateSubject($newEmail[1]);
                    $email->setTemplateType(1);
                    if(array_key_exists(4,$newEmail))
                    {
                        $email->setSendMidnight($newEmail[4]);
                    }
                    try
                    {
                        $email->save();
                        $this->setDefaultEmailMarketing($newEmail[3],$email->getId());
                    } catch (\Exception $e) {
                        throw $e;
                    }
                }
            }//end foreach
            //not found
            if(!$matched && !empty($newEmail))
            {
                $templateObject = $this->templateFactory->create();
                try{
                    $body = $this->dataHelper->getTxtContent
                    (
                        $newEmail[2]
                    );
                    $templateObject->setTemplateCode($newEmail[0]);
                    $templateObject->setTemplateText($body);
                    $templateObject->setTemplateSubject($newEmail[1]);
                    $templateObject->setTemplateType(1);
                    $templateObject->save();
                    //set configuration value
                    if($newEmail[3])
                    {
                        $this->configWriter->save
                        (
                            $newEmail[3],
                            $templateObject->getId()
                        );
                    }

                }catch(\Exception $e){
                    throw $e;
                }
            }
        }//end foreach setup

    }//end function

    /**
     * @param $path
     * @param $id
     * @throws \Exception
     */
    public function setDefaultEmailMarketing($path, $id)
    {
        if($path && $id)
        {
            try{
                $this->configWriter->save
                (
                    $path,
                    $id
                );
            } catch(\Exception $e)
            {
                throw $e;
            }
        }
    }//end function

    public function changeFooterEmailContent()
    {
        $content = $this->dataHelper->getTxtContent('footer.txt');
        try{
            $this->configWriter->save
            (
                'trans_email/emailtemplate/emailfooter',
                $content
            );
        }catch(\Exception $e)
        {
            throw $e;
        }

    }

    /**
     *
     */
    public function installErrorCronEmail()
    {
        $templateCode = 'Error cron email - {{var cronName}}';
        $templateObject = $this->templateFactory->create();
        $templateObject->load($templateCode, 'template_code');
        if(!$templateObject->getId()){
            $templateObject->setTemplateCode(__($templateCode));
            $templateObject->setTemplateText(__('Dear Admin \r\n {{var cronName}} has been errored. \r\n\r\n Please check again'));
            $templateObject->setTemplateSubject(__('Error cron email'));
            $templateObject->setTemplateType(1); // text email
            $templateObject->setSendMidnight(1);
            $templateObject->save();
            //save config
            $this->configWriter->save
            (
                'system/error_cron/error_cron_template',
                $templateObject->getId()
            );
        }
    }
}