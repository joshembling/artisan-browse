<?php

namespace JoshEmbling\ArtisanBrowse\Commands;

use Illuminate\Console\Command;

class ArtisanBrowseCommand extends Command
{
    public $signature = 'artisan-browse';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
