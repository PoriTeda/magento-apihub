<?php

namespace Wyomind\PointOfSale\Helper;

/**
 * Core general helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    protected $_coreHelper = null;
    protected $_regionModel = null;
    protected $_localLists = null;
    protected $_storeManager = null;
    protected $_imageAdapterFactory = null;
    protected $_coreDate = null;
    protected $_directoryList = null;
    protected $_file = null;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Wyomind\Core\Helper\Data $coreHelper,
        \Magento\Directory\Model\Region $regionModel,
        \Magento\Framework\Locale\ListsInterface $localeLists,
        \Magento\Framework\Stdlib\DateTime\DateTime $coreDate,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Framework\Image\AdapterFactory $imageAdapterFactory,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Io\File $file
    ) {
        parent::__construct($context);
        $this->_coreHelper = $coreHelper;
        $this->_coreDate = $coreDate;
        $this->_regionModel = $regionModel;
        $this->_localLists = $localeLists;
        $this->_storeManager = $storeManagerInterface;
        $this->_imageAdapterFactory = $imageAdapterFactory;
        $this->_directoryList = $directoryList;
        $this->_file = $file;
    }

    public function getImage(
        $src,
        $xSize = 150,
        $ySize = 150,
        $keepRatio = true,
        $styles = ""
    ) {

        if ($src != "") {
            $path = $this->_getMediaDir() . DIRECTORY_SEPARATOR . $src;
            if ($this->_file->fileExists($path)) {
                $part = explode("/", $src);
                $basename = array_pop($part);

                $cachePath = $this->_getMediaDir() . DIRECTORY_SEPARATOR . "stores" . DIRECTORY_SEPARATOR . "cache" . DIRECTORY_SEPARATOR . $basename;

                $image = new \Magento\Framework\Image($this->_imageAdapterFactory->create(), $path);
                $image->constrainOnly(false);
                $image->keepAspectRatio($keepRatio);

                $image->setImageBackgroundColor(0xFFFFFF);
                $image->keepTransparency(true);
                $image->resize($xSize, $ySize);
                $image->save($cachePath);


                $baseurl = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA, false);
                return "<img style='" . $styles . "' src='" . $baseurl . "stores/cache/" . $basename . "'/>";
            } else {
                return;
            }
        } else {
            return;
        }
    }

    public function getStoreDescription($place)
    {
        $pattern = $this->_coreHelper->getStoreConfig('pointofsale/settings/pattern');
        $replace = [];
        $replace['image'] = $this->getImage($place->getImage(), 150, 150, true, "float:right");

        $replace['name'] = $place->getStoreName();
        $replace['code'] = $place->getStoreCode();

        $replace['address_1'] = $place->getAddressLine1();
        $replace['address_2'] = $place->getAddressLine2();
        $replace['zipcode'] = $place->getPostalCode();
        $replace['city'] = $place->getCity();

        if ($place->getState()) {
            $replace['state'] = $this->_regionModel->loadByCode($place->getState(), $place->getCountryCode())->getName();
        } else {
            $replace['state'] = null;
        }
        $replace['country'] = $this->_localLists->getCountryTranslation($place->getCountryCode());


        $replace['phone'] = $place->getMainPhone();
        $replace['email'] = $place->getEmail();
        $replace['description'] = $place->getDescription();

        $replace['hours'] = $this->getHours($place->getHours());

        $search = ["{{image}}", "{{name}}", "{{code}}", "{{address_1}}", "{{address_2}}", "{{zipcode}}", "{{city}}", "{{state}}", "{{country}}", "{{phone}}", "{{email}}", "{{description}}", "{{hours}}"];

        return preg_replace('#(?:<br\s*/?>\s*?){2,}#', "<br>", nl2br(str_replace($search, $replace, $pattern)));
    }

    public function getHours($data)
    {
        $data = json_decode($data);
        $content = null;
        if ($data != null) {
            foreach ($data as $day => $hours) {
                $content .= __($day);
                $f = explode(':', $hours->from);
                $t = explode(':', $hours->to);
                $from = $f[0] * 60 * 60 + $f[1] * 60 + 1;
                $to = $t[0] * 60 * 60 + $t[1] * 60 + 1;
                $lfrom = 0;
                $lto = 0;
                if (isset($hours->lunch_from) && isset($hours->lunch_to)) {
                    $lf = explode(':', $hours->lunch_from);
                    $lt = explode(':', $hours->lunch_to);
                    $lfrom = $lf[0] * 60 * 60 + $lf[1] * 60 + 1;
                    $lto = $lt[0] * 60 * 60 + $lt[1] * 60 + 1;
                }
                
                $content .= ' '
                        . $this->_coreDate->gmtDate($this->_coreHelper->getStoreConfig("pointofsale/settings/time"), $from)
                        . ($lfrom != 0 ? '-'.date($this->_coreHelper->getStoreConfig("pointofsale/settings/time"), $lfrom) : '')
                        . ' - '
                        . ($lto != 0 ? date($this->_coreHelper->getStoreConfig("pointofsale/settings/time"), $lto).'-' : '')
                        . date($this->_coreHelper->getStoreConfig("pointofsale/settings/time"), $to)
                        . "<br>";
            }
        }
        return $content;
    }

    protected function _getMediaDir()
    {
        return $this->_directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
    }

   

    public function getGoogleApiKey()
    {
        return $this->_coreHelper->getStoreConfig('pointofsale/settings/googleapi');
    }
}
