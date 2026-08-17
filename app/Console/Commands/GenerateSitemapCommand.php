<?php

namespace App\Console\Commands;

use App\Jobs\GenerateSitemap;
use Illuminate\Console\Command;

class GenerateSitemapCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'app:generate-sitemap';

    /**
     * @var string
     */
    protected $description = 'Regenerate public/sitemap.xml immediately (synchronously, bypassing the queue)';

    public function handle(): void
    {
        GenerateSitemap::dispatchSync();

        $this->info('Sitemap regenerated at '.public_path('sitemap.xml'));
    }
}
