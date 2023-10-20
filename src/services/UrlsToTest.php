<?php

namespace astuteo\astuteotoolkit\services;

use craft\elements\Entry;
use craft\elements\Category;
use Illuminate\Support\Collection;

class UrlsToTest
{

    const MODE_CSS_TESTING = 'css-testing';

    /**
     * Retrieve all URLs for testing based on the given parameters.
     *
     * @param string $type The type of URL to return: 'url' or 'uri'.
     * @param int $limit The maximum number of URLs to return.
     * @param string|null $mode Optional mode to alter behavior (e.g., 'css-testing').
     * @return array An array of URLs.
     */
    public function getAllUrls($type = 'url', int $limit = 1, string $mode = null): array
    {
        $urls = array_merge(
            $this->getUrlsFromSections($limit, $type, $mode),
            $this->getUrlsFromCategoryGroups($limit, $type, $mode)
        );
        return array_values(array_filter($urls, fn($url) => !is_null($url)));
    }

    /**
     * Retrieve URLs from all sections.
     */
    private function getUrlsFromSections(int $limit, string $type, string $mode = null): array
    {
        $sections = \Craft::$app->sections->getAllSections();
        return Collection::make($sections)
            ->flatMap(fn($section) => $this->getUrlsFromSection($section, $limit, $type, $mode))
            ->all();
    }

    /**
     * Retrieve URLs from a single section.
     */
    private function getUrlsFromSection($section, int $limit, string $type, string $mode = null): array
    {
        $maxEntries = $this->getMaxEntries($section, $limit);
        return $this->getProcessedUrls($maxEntries, $type, $mode);
    }

    /**
     * Helper function to process URLs.
     */
    private function getProcessedUrls($elements, string $type, string $mode = null): array
    {
        return $elements
            ->map(function ($element) use ($type, $mode) {
                if ($element->uri === null) return null;
                $uri = $element->uri === '__home__' ? '' : $element->uri;
                return $mode === self::MODE_CSS_TESTING ? '/' . $uri : ($type === 'uri' ? $uri : $element->url);
            })
            ->all();
    }

    /**
     * Retrieve URLs from all category groups.
     */
    private function getUrlsFromCategoryGroups(int $limit, string $type, string $mode = null): array
    {
        $categoryGroups = \Craft::$app->categories->getAllGroups();
        return Collection::make($categoryGroups)
            ->flatMap(fn($group) => $this->getUrlsFromCategoryGroup($group, $limit, $type, $mode))
            ->all();
    }

    /**
     * Retrieve URLs from a single category group.
     */
    private function getUrlsFromCategoryGroup($group, int $limit, string $type, string $mode = null): array
    {
        $maxCategories = $this->getMaxCategories($group, $limit);
        return $this->getProcessedUrls($maxCategories, $type, $mode);
    }

    /**
     * Get the maximum number of entries for a specific section, limited by $limit.
     */
    private function getMaxEntries($section, int $limit): Collection
    {
        if ($section->type === 'single') {
            return Collection::make([Entry::find()->sectionId($section->id)->one()]);
        }

        $entryTypes = $section->getEntryTypes();
        $entries = Collection::make();

        foreach ($entryTypes as $entryType) {
            $entryCollection = Entry::find()->sectionId($section->id)->typeId($entryType->id)->all();
            $sortedEntries = Collection::make($entryCollection)
                ->sortByDesc(fn($entry) => count(array_filter($entry->getFieldValues())))
                ->take($limit);
            $entries = $entries->concat($sortedEntries);
        }

        return $entries;
    }

    /**
     * Get the maximum number of categories for a specific category group, limited by $limit.
     */
    private function getMaxCategories($group, int $limit): Collection
    {
        $categories = Category::find()->groupId($group->id)->all();
        return Collection::make($categories)
            ->sortByDesc(fn($category) => count(array_filter($category->getFieldValues())))
            ->take($limit);
    }

}
