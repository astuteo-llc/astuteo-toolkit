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
        $limit = \Craft::$app->request->getQueryParam('limit');
        $limit = $limit ? (int) $limit : 1;
        $urls = (new UrlsToTest)->getAllUrls($type, $limit);
        return $this->asJson($urls);
    }
}
