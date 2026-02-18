<?php

namespace JoshEmbling\ArtisanBrowse\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \JoshEmbling\ArtisanBrowse\ArtisanBrowse
 */
class ArtisanBrowse extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \JoshEmbling\ArtisanBrowse\ArtisanBrowse::class;
    }
}
