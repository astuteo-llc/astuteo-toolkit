<?php

namespace astuteo\astuteotoolkit\services;
use craft\base\Component;
use Craft;

/**
 * Class CpNavService
 *
 * @package astuteo\astuteotoolkit\services
 */
class CpNavService extends Component {
    // Add CP sidebar links to admin in dev mode
    // https://github.com/vigetlabs/craft-viget-base/
    public function addNav(array $items =[]) {
        $items[] = [
            'url' => 'settings/sections',
            'label' => Craft::t('app', 'Sections'),
            'fontIcon' => '',
        ];

        $items[] = [
            'url' => 'settings/fields',
            'label' => Craft::t('app', 'Fields'),
            'fontIcon' => '',
        ];
        return $items;
    }
}
