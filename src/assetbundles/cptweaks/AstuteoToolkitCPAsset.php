<?php

namespace astuteo\astuteotoolkit\assetbundles\cptweaks;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;


class AstuteoToolkitCPAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = "@astuteo/astuteotoolkit/assetbundles/cptweaks/dist";
        $this->depends = [
            CpAsset::class,
        ];
        //$this->js = [
        //    'js/reports.js',
        //];
        $this->css = [
            'css/astuteo-cp-tweaks.css',
        ];
        parent::init();
    }
}
