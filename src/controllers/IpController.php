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
        // Check if authentication is required
        $settings = AstuteoToolkit::$plugin->getSettings();
        $requiredToken = $settings->getIpControllerToken();

        // If an authentication key is set in settings, validate the request
        if (!empty($requiredToken)) {
            // Check for authKey first, fall back to token for backward compatibility
            $requestToken = Craft::$app->getRequest()->getQueryParam('authKey');

            // If no authentication key provided or it doesn't match, return 403 Forbidden
            if (empty($requestToken) || $requestToken !== $requiredToken) {
                return $this->asJson([
                    'error' => 'Invalid or missing authentication key',
                    'status' => 403
                ])->setStatusCode(403);
            }
        }

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
