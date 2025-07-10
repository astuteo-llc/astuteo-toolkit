<?php

namespace astuteo\astuteotoolkit\helpers;

use craft\elements\Asset;
use Craft;
use astuteo\astuteotoolkit\helpers\LoggerHelper;

class UploadHelper {
    public function downloadFile($src, $dest) {
        LoggerHelper::info('Attempting to download file from: ' . $src);
        $ch = curl_init($src);
        $fp = fopen($dest, 'wb');
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_exec($ch);
        $response = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        fclose($fp);
        if($response == 200) {
            LoggerHelper::info('Successfully downloaded file to: ' . $dest);
            return $dest;
        }
        LoggerHelper::error('Failed to download file from: ' . $src . ' (HTTP code: ' . $response . ')' . ($error ? ' Error: ' . $error : ''));
        return false;
    }

    public function uploadToVolume($volumeId, $src, $filename): bool
    {
        LoggerHelper::info('Attempting to upload file to volume: ' . $filename);
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
            LoggerHelper::error('Failed to save asset: ' . $filename . '. Errors: ' . json_encode($asset->getErrors()));
            return false;
        }
        LoggerHelper::info('Successfully uploaded file to volume: ' . $filename);
        return true;
    }
}
