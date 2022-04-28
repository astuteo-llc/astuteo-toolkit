<?php
/**
 * Astuteo Toolkit plugin for Craft CMS 3.x
 *
 * test
 *
 * @link      https://astuteo.com
 * @copyright Copyright (c) 2021 astuteo
 */

namespace astuteo\astuteotoolkit\controllers;

use Craft;
use craft\web\Controller;
use craft\helpers\UrlHelper;
use astuteo\astuteotoolkit\services\AutoLoginService;

class AutoLoginController extends Controller {

    protected array|int|bool $allowAnonymous  = ['index'];

    public function actionIndex() {
        $loggedIn = AutoLoginService::login();
        Craft::$app->response->redirect(UrlHelper::cpUrl('/admin'));
        return Craft::$app->end();
    }

}
