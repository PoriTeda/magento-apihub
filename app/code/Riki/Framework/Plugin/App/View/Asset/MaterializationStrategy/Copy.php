<?php

namespace Riki\Framework\Plugin\App\View\Asset\MaterializationStrategy;

use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

class Copy
{
    /**
     * @param \Magento\Framework\App\View\Asset\MaterializationStrategy\Copy $subject
     * @param callable $proceed
     * @param WriteInterface $rootDir
     * @param WriteInterface $targetDir
     * @param $sourcePath
     * @param $destinationPath
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function aroundPublishFile(
        \Magento\Framework\App\View\Asset\MaterializationStrategy\Copy $subject,
        callable $proceed,
        WriteInterface $rootDir,
        WriteInterface $targetDir,
        $sourcePath,
        $destinationPath
    )
    {
        $result = $proceed($rootDir, $targetDir, $sourcePath, $destinationPath);

        if ($result && strpos($sourcePath, '.html') && !strpos($sourcePath, 'blank.html')) {
            $clickJacking = <<<HTML

<!-- clickjacking -->
<style id="antiClickjack">body{display:none !important;}</style> 
<script type="text/javascript">
    if (self == top) {
        var antiClickjack = document.getElementById("antiClickjack"); 
       	antiClickjack.parentNode.removeChild(antiClickjack);
   	} else {
        top.location = encodeURI(self.location); 
   	}
</script>
<!-- clickjacking -->
HTML;
            $parentDirectory = $targetDir->getRelativePath(DirectoryList::PUB . '/' . DirectoryList::STATIC_VIEW);
            $rootDir->writeFile($parentDirectory . '/' . $destinationPath, $clickJacking, 'a+');
        }

        return $result;
    }
}
