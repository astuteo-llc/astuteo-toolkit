<?php

namespace astuteo\astuteotoolkit\controllers;

use astuteo\astuteotoolkit\AstuteoToolkit;
use Craft;
use craft\web\Controller;
use yii\web\Response;

class IpController extends Controller
{
    /**
     * Authentication key parameter name used for IP lookup requests
     */
    public const AUTH_KEY = 'authKey';

    protected array|bool|int $allowAnonymous = true;

    public function actionInfo(): Response
    {
        // Check if authentication is required
        $settings = AstuteoToolkit::$plugin->getSettings();
        $requiredToken = $settings->getIpControllerToken();
        $validateDomain = $settings->getValidateDomain();

        if (!empty($requiredToken)) {
            $requestToken = Craft::$app->getRequest()->getQueryParam(self::AUTH_KEY);
            if (empty($requestToken) || $requestToken !== $requiredToken) {
                return $this->asJson([
                    'error' => 'Invalid or missing authentication key',
                    'status' => 403
                ])->setStatusCode(403);
            }
        }

        if($validateDomain) {
            // Basic validation on domain names to make sure
            // it's serving the same server that's requesting
            $request = Craft::$app->getRequest();
            $serverDomain = $request->getServerName();
            $referer = $request->getReferrer();

            if ($referer) {
                $refererDomain = parse_url($referer, PHP_URL_HOST);

                if (!$refererDomain || !$this->domainsMatch($serverDomain, $refererDomain)) {
                    return $this->asJson([
                        'error' => 'Access denied: Domain mismatch',
                        'status' => 403
                    ])->setStatusCode(403);
                }
            }
        }

        $ip = Craft::$app->getRequest()->getUserIP();
        $lookup = AstuteoToolkit::$plugin->ipLookup->lookup($ip);

        return $this->asJson($lookup);
    }

    private function domainsMatch(string $serverDomain, string $requestDomain): bool
    {
        // Remove www. prefix if present
        $serverDomain = preg_replace('/^www\./', '', strtolower($serverDomain));
        $requestDomain = preg_replace('/^www\./', '', strtolower($requestDomain));

        return $serverDomain === $requestDomain;
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
