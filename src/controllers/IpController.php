<?php

namespace astuteo\astuteotoolkit\controllers;

use astuteo\astuteotoolkit\AstuteoToolkit;
use Craft;
use craft\web\Controller;
use yii\web\Response;

class IpController extends Controller
{
    protected array|bool|int $allowAnonymous = true;

    public function actionInfo(): Response
    {
        $ip = Craft::$app->getRequest()->getUserIP();
        $lookup = AstuteoToolkit::$plugin->ipLookup->lookup($ip);
        
        return $this->asJson($lookup);
    }
} 