<?php

namespace astuteo\astuteotoolkit\services;

use craft\elements\Entry;
use craft\elements\Category;

class UrlsToTest
{
    public function getAllUrls($type = 'url'): array
    {
        $urls = [];

        // Retrieve all sections
        $sections = \Craft::$app->sections->getAllSections();

        // Iterate through sections and their respective entry types
        foreach ($sections as $section) {
            if ($section->type === 'single') {
                $entry = Entry::find()->sectionId($section->id)->one();
                if ($entry && $entry->url !== null) {
                    $urls[] = $type === 'uri' ? $entry->uri : $entry->url;
                }
            } else {
                $entryTypes = $section->getEntryTypes();

                foreach ($entryTypes as $entryType) {
                    // Get 20 entries of each entry type
                    $criteria = Entry::find();
                    $criteria->sectionId = $section->id;
                    $criteria->typeId = $entryType->id;
                    $criteria->limit = 20;

                    $entries = $criteria->all();
                    $maxFilledFields = 0;
                    $maxEntry = null;

                    // Iterate through the entries to find the entry with the most filled fields
                    foreach ($entries as $entry) {
                        $filledFields = 0;

                        foreach ($entry->getFieldValues() as $fieldValue) {
                            if (!empty($fieldValue)) {
                                $filledFields++;
                            }
                        }

                        if ($filledFields > $maxFilledFields) {
                            $maxFilledFields = $filledFields;
                            $maxEntry = $entry;
                        }
                    }
                    if ($maxEntry->url !== null) {
                        $urls[] = $type === 'uri' ? $maxEntry->uri :  $maxEntry->url;
                    }
                }
            }
        }

        // Retrieve all category groups
        $categoryGroups = \Craft::$app->categories->getAllGroups();

        // Iterate through category groups
        foreach ($categoryGroups as $categoryGroup) {
            // Get 20 categories from each group
            $criteria = Category::find();
            $criteria->groupId = $categoryGroup->id;
            $criteria->limit = 20;

            $categories = $criteria->all();
            $maxFilledFields = 0;
            $maxCategory = null;

            // Iterate through the categories to find the category with the most filled fields
            foreach ($categories as $category) {
                $filledFields = 0;

                foreach ($category->getFieldValues() as $fieldValue) {
                    if (!empty($fieldValue)) {
                        $filledFields++;
                    }
                }

                if ($filledFields > $maxFilledFields) {
                    $maxFilledFields = $filledFields;
                    $maxCategory = $category;
                }
            }
            if ($maxCategory !== null) {
                $urls[] = $type === 'uri' ? $maxCategory->uri : $maxCategory->url;
            }
        }

        return $urls;
    }
}
