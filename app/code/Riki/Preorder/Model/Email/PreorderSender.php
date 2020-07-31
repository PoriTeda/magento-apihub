<?php
namespace Riki\Preorder\Model\Email;

use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Store\Model\StoreManagerInterface;


class PreorderSender
{
    const XML_PATH_CONVERTED_PRODUCT_EMAIL_IDENTITY = 'rikipreorder/email/product_identity';
    const XML_PATH_CONVERTED_PRODUCT_EMAIL_TEMPLATE = 'rikipreorder/email/product_template';
    const XML_PATH_CONVERTED_PRODUCT_EMAIL_RECEIVER_ADDRESS = 'rikipreorder/email/product_receiver_address';
    const XML_PATH_CONVERTED_PRODUCT_EMAIL_RECEIVER_NAME = 'rikipreorder/email/product_receiver_name';
    const XML_PATH_EMAIL_COPY_TO_EMAIL_COPY_TO = 'rikipreorder/email/copy_to';

    protected $_scopeConfig;

    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var \Riki\Preorder\Logger\Logger
     */
    protected $logger;

    protected $storeManager;

    public function __construct(
        TransportBuilder $transportBuilder,
        \Riki\Preorder\Logger\Logger $logger,
        StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ){

        $this->_scopeConfig = $scopeConfig;
        $this->transportBuilder = $transportBuilder;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
    }

    /**
     * @param $productId
     * @return bool
     */
    public function notifyConvertedProduct($productId){

        $templateId = $this->_getConfig(self::XML_PATH_CONVERTED_PRODUCT_EMAIL_TEMPLATE);
        $toName = $this->_getConfig(self::XML_PATH_CONVERTED_PRODUCT_EMAIL_RECEIVER_NAME);
        $toEmail = $this->_getConfig(self::XML_PATH_CONVERTED_PRODUCT_EMAIL_RECEIVER_ADDRESS);
        $copyTo = $this->_getConfig(self::XML_PATH_EMAIL_COPY_TO_EMAIL_COPY_TO);
        $identity = $this->_getConfig(self::XML_PATH_CONVERTED_PRODUCT_EMAIL_IDENTITY);

        $vars = [
            'product_id'    =>  $productId,
            'admin_name'    =>  $toName
        ];

        try {
            $this->_send(
                $toEmail,
                $toName,
                empty($copyTo)? [] : explode(',', $copyTo),
                $templateId,
                $vars,
                $identity
            );
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return true;
    }

    /**
     * @param $email
     * @param $name
     * @param $copyTo
     * @param $templateId
     * @param $templateVars
     * @param $emailIdentity
     */
    protected function _send($email, $name, $copyTo, $templateId, $templateVars, $emailIdentity)
    {
        $this->transportBuilder->setTemplateIdentifier($templateId);
        $this->transportBuilder->setTemplateVars($templateVars);
        $this->transportBuilder->setTemplateOptions($this->getTemplateOptions());
        $this->transportBuilder->setFrom($emailIdentity);

        $this->transportBuilder->addTo(
            $email,
            $name
        );

        if (!empty($copyTo)) {
            foreach ($copyTo as $email) {
                $this->transportBuilder->addBcc($email);
            }
        }

        $transport = $this->transportBuilder->getTransport();
        $transport->sendMessage();
    }

    /**
     * @return array
     */
    protected function getTemplateOptions()
    {
        return [
            'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
            'store' => $this->getStore()->getStoreId()
        ];
    }

    /**
     * Return store
     *
     * @return Store
     */
    public function getStore()
    {
        return $this->storeManager->getStore();
    }

    /**
     * @param $path
     * @return mixed
     */
    protected function _getConfig($path){
        return $this->_scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
}
