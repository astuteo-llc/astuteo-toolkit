<?php

namespace astuteo\astuteotoolkit;

use Craft;
use craft\queue\BaseJob;

use astuteo\astuteotoolkit\services\ReportStatusService;

/**
 * Job to make the request to the Airtable inventory
 */
class ReportJob extends BaseJob
{
    /**
     * @inheritdoc
     */
    public function execute($queue)
    {
        ReportStatusService::makeRequest();
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription()
    {
        return 'Reporting Status';
    }
}