<?php

namespace Wyomind\PointOfSale\Controller\Adminhtml\Manage;

class ImportCsv extends \Wyomind\PointOfSale\Controller\Adminhtml\PointOfSale
{

    public function execute()
    {
        $this->_uploader = new \Magento\Framework\File\Uploader("csv-file");
        if ($this->_uploader->getFileExtension() != "csv") {
            $this->messageManager->addError(__("Wrong file type (") . $this->_uploader->getFileExtension() . __(").<br>Choose a csv file."));
        } else {
            $this->_uploader->save("var/tmp", "import-pointofsale.csv");
            // récuperer le contenu
            $file = new \Magento\Framework\Filesystem\Driver\File;
            $csv = new \Magento\Framework\File\Csv($file);
            $csv->setDelimiter("\t");
            $content = $csv->getData("var/tmp/" . $this->_uploader->getUploadedFileName());
            $model = $this->_posModelFactory->create();
            $fields = $content[0];
            $i = 1;
            while (isset($content[$i])) {
                foreach ($content[$i] as $key => $value) {
                    if (isset($fields[$key])) {
                        $data[$fields[$key]] = $value;
                    }
                }
                $model->setData($data)->save();
                $i++;
            }
           
            $file->deleteFile("var/tmp/" . $this->_uploader->getUploadedFileName());
        }
        $this->messageManager->addSuccess(($i - 1) . __(" places have been imported."));
        $result = $this->_resultRedirectFactory->create()->setPath("pointofsale/manage");
        return $result;
    }
}
