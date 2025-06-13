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

    /**
     * Clear all IP lookup cache entries
     * 
     * @return Response
     */
    public function actionClearCache(): Response
    {
        $this->requireAdmin();

        $success = AstuteoToolkit::$plugin->ipLookup->clearCache();

        if ($success) {
            Craft::$app->getSession()->setNotice(Craft::t('astuteo-toolkit', 'IP lookup cache cleared.'));
        } else {
            Craft::$app->getSession()->setError(Craft::t('astuteo-toolkit', 'Could not clear IP lookup cache.'));
        }

        return $this->redirectToPostedUrl();
    }
} 
