<?php

namespace astuteo\astuteotoolkit\services;

use astuteo\astuteotoolkit\AstuteoToolkit;
use Craft;
use yii\base\Component;

class IpLookupService extends Component
{
    public function lookup(string $ip): ?array
    {
        $token = AstuteoToolkit::$plugin->getSettings()->ipinfoToken;
        if (empty($token)) {
            Craft::error('IPInfo token not configured', __METHOD__);
            return null;
        }

        $url = "https://ipinfo.io/{$ip}/json?token={$token}";

        try {
            $client = Craft::createGuzzleClient();
            $response = $client->get($url);
            $data = json_decode((string)$response->getBody(), true);

            return [
                'asn' => $data['asn'] ?? null,
                'company_name' => $data['as_name'] ?? null,
                'country' => $data['country'] ?? null,
                'continent' => $data['continent'] ?? null,
            ];
        } catch (\Throwable $e) {
            Craft::error("IP lookup failed: " . $e->getMessage(), __METHOD__);
            return null;
        }
    }
} 
