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
    private const STRONG_ISP_KEYWORDS = [
        'akamai',
        'amazon',
        'astound',
        'at t',
        'at & t',
        'at&t',
        'att',
        'aws',
        'baidu',
        'bell',
        'bt',
        'centurylink',
        'charter',
        'china telecom',
        'china unicom',
        'cincinnati bell',
        'cloudflare',
        'comcast',
        'consolidated',
        'cox',
        'deutsche telekom',
        'dt',
        'earthlink',
        'eir',
        'fastly',
        'frontier',
        'google',
        'grande',
        'hughesnet',
        'ibm',
        'mediacom',
        'microsoft',
        'optimum',
        'oracle',
        'orange',
        'rcn',
        'rogers',
        'spectrum',
        'shaw',
        'singtel',
        'sk broadband',
        'softbank',
        'sprint',
        'suddenlink',
        'tds',
        'telefonica',
        'telia',
        'telstra',
        'telus',
        't-mobile',
        'tmobile',
        'verizon',
        'vodafone',
        'vtr',
        'wave',
        'wide open',
        'wideopen',
        'windstream',
        'wow',
        'xfinity',
        'ziggo',
    ];

    private const ISP_SUFFIXES = [
        'broadband',
        'business',
        'cable',
        'cellular',
        'cloud',
        'co',
        'company',
        'communications',
        'consolidated',
        'corporation',
        'corp',
        'fiber',
        'group',
        'holding',
        'holdings',
        'hosting',
        'inc',
        'internet',
        'limited',
        'llc',
        'mobile',
        'network',
        'provider',
        'server',
        'service',
        'services',
        'solutions',
        'systems',
        'technology',
        'technologies',
        'telecom',
        'telecommunication',
        'wireless',
    ];

    /**
     * List of specific combinations that should be detected as ISPs
     * Often these are VPN providers or hosting services where the name contains
     * a legitimate company name (e.g., "Micron") that shouldn't be flagged alone,
     * but when combined with certain terms indicates an ISP or VPN service
     */
    private const SPECIFIC_ISP_COMBINATIONS = [
        'micron hosting',
        't mobile usa',
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

        // Check for specific ISP/VPN combinations
        foreach (self::SPECIFIC_ISP_COMBINATIONS as $combination) {
            if (str_contains($orgName, $combination)) {
                return true;
            }
        }

        // Strong keyword match
        foreach (self::STRONG_ISP_KEYWORDS as $keyword) {
            if (str_contains($orgName, $keyword)) {
                return true;
            }
        }

        // Suffix + strong keyword
        if (preg_match('/(' . implode('|', self::ISP_SUFFIXES) . ')$/i', $orgName)) {
            foreach (self::STRONG_ISP_KEYWORDS as $keyword) {
                if (str_contains($orgName, $keyword)) {
                    return true;
                }
            }
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

        // Avoid a unique bug where it was applying it twice
        if (str_contains($organizationName, '(Likely-ISP)')) {
            return $organizationName;
        }

        if ($this->isLikelyIsp($organizationName)) {
            return "{$organizationName} (Likely-ISP)";
        }

        return $organizationName;
    }

    public function getIspDetection(?string $organizationName): array
    {
        $isIsp = $this->isLikelyIsp($organizationName);
        $formatted = $this->formatIspName($organizationName);
        return [
            'organization' => $formatted,
            'is_isp' => $isIsp,
        ];
    }
}
