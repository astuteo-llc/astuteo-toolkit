<?php

namespace astuteo\astuteotoolkit\models;

use craft\base\Model;

/**
 * ISP Detector model for identifying and marking ISP organizations
 */
class IspDetector extends Model
{
    /**
     * List of common ISP keywords to check for in organization names
     */
    private const ISP_KEYWORDS = [
        'telecom',
        'communications',
        'internet',
        'isp',
        'broadband',
        'network',
        'fiber',
        'cable',
        'wireless',
        'mobile',
        'cellular',
        'hosting',
        'cloud',
        'server',
        'data center',
        'datacenter',
        'provider',
        'service',
        'telco',
        'telecommunication',
        'communication',
        'digital',
        'technologies',
        'technology',
        'solutions',
        'connect',
        'connectivity',
        'net',
        'online',
        'web',
        'media',
        'it',
        'information technology',
        'telecom',
        'telephone',
        'phone',
        'cell',
        'wireless',
        'wifi',
        'wi-fi',
        'satellite',
        'dsl',
        'adsl',
        'vdsl',
        'fios',
        'optic',
        'optical',
        'comcast',
        'verizon',
        'at&t',
        'at t',
        'at & t',
        'sprint',
        't-mobile',
        't mobile',
        'tmobile',
        'cox',
        'charter',
        'spectrum',
        'centurylink',
        'century link',
        'frontier',
        'windstream',
        'earthlink',
        'earth link',
        'hughesnet',
        'hughes net',
        'dish',
        'directv',
        'direct tv',
        'xfinity',
        'optimum',
        'altice',
        'suddenlink',
        'sudden link',
        'mediacom',
        'media com',
        'wow',
        'wideopen',
        'wide open',
        'rcn',
        'astound',
        'grande',
        'wave',
        'tds',
        'consolidated',
        'cincinnati bell',
        'cincinnati',
        'bell',
        'fairpoint',
        'fair point',
        'google',
        'amazon',
        'aws',
        'azure',
        'microsoft',
        'ibm',
        'oracle',
        'alibaba',
        'tencent',
        'baidu',
        'cloudflare',
        'akamai',
        'fastly',
        'cdn',
        'content delivery',
        'edge',
        'proxy',
        'vpn',
        'virtual private',
        'private',
        'dedicated',
        'managed',
        'unmanaged',
        'shared',
        'reseller',
        'colocation',
        'co-location',
        'colo',
        'rack',
        'server',
        'virtual',
        'vps',
        'cloud',
        'iaas',
        'paas',
        'saas',
        'infrastructure',
        'platform',
        'software',
        'service',
        'as a service',
        'on demand',
        'on-demand',
        'on prem',
        'on-prem',
        'on premise',
        'on-premise',
        'on premises',
        'on-premises',
        'off prem',
        'off-prem',
        'off premise',
        'off-premise',
        'off premises',
        'off-premises',
        'hybrid',
        'multi-cloud',
        'multicloud',
        'multi cloud',
        'public cloud',
        'private cloud',
        'community cloud',
        'distributed cloud',
        'edge cloud',
        'fog computing',
        'fog',
        'edge computing',
        'edge',
        'iot',
        'internet of things',
        'things',
        'devices',
        'sensors',
        'actuators',
        'controllers',
        'gateways',
        'routers',
        'switches',
        'hubs',
        'bridges',
        'repeaters',
        'modems',
        'access points',
        'ap',
        'wap',
        'wireless access',
        'lan',
        'wan',
        'man',
        'pan',
        'san',
        'can',
        'dan',
        'local area',
        'wide area',
        'metropolitan area',
        'personal area',
        'storage area',
        'campus area',
        'desk area',
        'network',
        'networking',
        'networked',
        'networks',
        'net',
        'nets',
        'netting',
        'netted',
        'netters',
        'netter',
        'netters',
        'netter',
        'netters',
        'netter',
    ];

    /**
     * Check if an organization name is likely an ISP
     * 
     * @param string|null $organizationName The organization name to check
     * @return bool True if the organization is likely an ISP, false otherwise
     */
    public function isLikelyIsp(?string $organizationName): bool
    {
        if (empty($organizationName)) {
            return false;
        }

        $orgName = strtolower($organizationName);

        // Check for common ISP keywords
        foreach (self::ISP_KEYWORDS as $keyword) {
            if (str_contains($orgName, $keyword)) {
                return true;
            }
        }

        // Check for common ISP patterns
        // Example: Names ending with LLC, Inc, Ltd, etc. that also contain network-related terms
        if (preg_match('/(llc|inc|ltd|limited|corporation|corp|co|company|group|holdings|services|solutions|technologies|technology|systems|communications|telecommunication|telecom|network|internet|broadband|fiber|cable|wireless|mobile|cellular|hosting|cloud|server|provider|service)$/i', $orgName)) {
            return true;
        }

        return false;
    }

    /**
     * Format an organization name to indicate it's likely an ISP
     * 
     * @param string|null $organizationName The organization name to format
     * @return string|null The formatted organization name or null if the input was null
     */
    public function formatIspName(?string $organizationName): ?string
    {
        if (empty($organizationName)) {
            return null;
        }

        if ($this->isLikelyIsp($organizationName)) {
            return "Likely-ISP ({$organizationName})";
        }

        return $organizationName;
    }
}