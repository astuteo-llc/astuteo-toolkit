<?php

namespace astuteo\astuteotoolkit\controllers;

use craft\web\Controller;
use astuteo\astuteotoolkit\services\UrlsToTest;

class TestUrlController extends Controller
{
    public function init(): void
    {
        parent::init();
        $this->requireLogin();
    }

    public function actionIndex()
    {
        $type = \Craft::$app->request->getQueryParam('type', 'url');
        $urls = (new UrlsToTest)->getAllUrls($type);
        return $this->asJson($urls);
    }
}
