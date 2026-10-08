<?php

namespace App\Providers\Filament;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Illuminate\Database\Eloquent\Model;

class CustomAvatarProvider implements AvatarProvider
{
    public function get(Model $record): string
    {
        if ($record->avatar && file_exists(public_path('storage/' . $record->avatar))) {
            return asset('storage/' . $record->avatar);
        }

        return 'https://ui-avatars.com/api/?name=' . urlencode($record->name);
    }
}
