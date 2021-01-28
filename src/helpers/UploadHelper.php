<?php

namespace astuteo\astuteotoolkit\helpers;

use craft\elements\Asset;
use Craft;

class UploadHelper {
    public function downloadFile($src, $dest) {

        $ch = curl_init($src);

        $fp = fopen($dest, 'wb');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_exec($ch);
        $response = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);
        if($response == 200) {
            return true;
        }
        return false;
    }



    public function uploadToVolume($volumeId, $src, $filename): bool
    {
        $folderId = Craft::$app->assets->getRootFolderByVolumeId($volumeId)['id'];
        $asset = new Asset();
        $asset->tempFilePath = $src;
        $asset->filename = $filename;
        $asset->newFolderId = $folderId;
        $asset->volumeId = $volumeId;
        $asset->avoidFilenameConflicts = false;
        $asset->setScenario(Asset::SCENARIO_CREATE);
        $result = Craft::$app->getElements()->saveElement($asset);
        if(!$result) {
            return false;
        }
        return true;
    }
}