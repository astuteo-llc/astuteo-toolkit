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
     * 
     * How it works:
     * - If an organization name contains ANY of these keywords, it will be identified as an ISP
     * - These are strong indicators on their own (e.g., 'comcast', 'verizon')
     * - Case-insensitive matching is used
     * 
     * When adding new keywords:
     * - Add common ISP/telecom company names
     * - Add well-known hosting/cloud provider names
     * - Keep entries lowercase
     * - For multi-word names, include variations (e.g., 'at&t', 'at t', 'at & t')
     * - Maintain alphabetical order for easier maintenance
     * - Avoid generic terms that might cause false positives
     */
    private const STRONG_ISP_KEYWORDS = [
        'adsl',
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
        'biznet',
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
        'jio',
        'mediacom',
        'microsoft',
        'mpls',
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
        'telecom',
        'telefonica',
        'telephone',
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

    /**
     * List of common suffixes found in ISP organization names
     * 
     * How it works:
     * - These suffixes are used in combination with STRONG_ISP_KEYWORDS
     * - If an organization name ends with any of these suffixes AND contains any STRONG_ISP_KEYWORDS,
     *   it will be identified as an ISP
     * - Used for detecting organizations like "Acme Networks" or "XYZ Telecom Ltd"
     * - Case-insensitive matching is used
     * 
     * When adding new suffixes:
     * - Add common business/organization type suffixes (e.g., 'inc', 'ltd')
     * - Add telecom/internet industry-specific suffixes (e.g., 'broadband', 'fiber')
     * - Keep entries lowercase
     * - Maintain alphabetical order for easier maintenance
     * - Avoid overly generic terms that might cause false positives
     */
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
        'ltd',
        'mobile',
        'net',
        'network',
        'networks',
        'provider',
        'sa',
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
     * 
     * How it works:
     * - If an organization name contains ANY of these exact combinations, it will be identified as an ISP
     * - Used for specific cases where a company name shouldn't be flagged alone
     * - These are direct substring matches (e.g., 'micron hosting' but not just 'micron')
     * - Case-insensitive matching is used
     * 
     * When adding new combinations:
     * - Add VPN providers or hosting services with legitimate company names
     * - Add specific combinations that would otherwise cause false negatives
     * - Keep entries lowercase
     * - Maintain alphabetical order for easier maintenance
     * - Be specific enough to avoid false positives
     */
    private const SPECIFIC_ISP_COMBINATIONS = [
        'aventice llc',
        'blazing seo, llc',
        'greenlight networks',
        'logicweb inc',
        'micron hosting',
        'nixi',
        't mobile usa',
        'zenlayer inc',
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
