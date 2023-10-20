<?php

namespace astuteo\astuteotoolkit\services;

use craft\elements\Entry;
use craft\elements\Category;
use Illuminate\Support\Collection;

class UrlsToTest
{
    public function getAllUrls($type = 'url', int $limit = 1): array
    {
        $urls = array_merge(
            $this->getUrlsFromSections($limit, $type),
            $this->getUrlsFromCategoryGroups($limit, $type)
        );
        return array_values(array_filter($urls, fn($url) => !is_null($url)));
    }

    private function getUrlsFromSections(int $limit, string $type): array
    {
        $sections = \Craft::$app->sections->getAllSections();

        return Collection::make($sections)
            ->flatMap(fn($section) => $this->getUrlsFromSection($section, $limit, $type))
            ->all();
    }

    private function getUrlsFromSection($section, int $limit, string $type): array
    {
        $maxEntries = $this->getMaxEntries($section, $limit);

        return $maxEntries
            ->map(fn($entry) => $type === 'uri' ? $entry->uri : $entry->url)
            ->all();
    }

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

    private function getUrlsFromCategoryGroups(int $limit, string $type): array
    {
        $categoryGroups = \Craft::$app->categories->getAllGroups();

        return Collection::make($categoryGroups)
            ->flatMap(fn($group) => $this->getUrlsFromCategoryGroup($group, $limit, $type))
            ->all();
    }

    private function getUrlsFromCategoryGroup($group, int $limit, string $type): array
    {
        $maxCategories = $this->getMaxCategories($group, $limit);

        return $maxCategories
            ->map(fn($category) => $type === 'uri' ? $category->uri : $category->url)
            ->all();
    }

    private function getMaxCategories($group, int $limit): Collection
    {
        $categories = Category::find()->groupId($group->id)->all();
        return Collection::make($categories)
            ->sortByDesc(fn($category) => count(array_filter($category->getFieldValues())))
            ->take($limit);
    }
}
