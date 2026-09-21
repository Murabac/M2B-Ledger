<?php

namespace App\Filament\AvatarProviders;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class GreenTintAvatarProvider implements AvatarProvider
{
    public function get(Model | Authenticatable $record): string
    {
        $initials = str(Filament::getNameForDefaultAvatar($record))
            ->trim()
            ->explode(' ')
            ->map(fn (string $segment): string => filled($segment) ? mb_strtoupper(mb_substr($segment, 0, 1)) : '')
            ->join('');

        $initials = mb_substr((string) $initials, 0, 2);

        if ($initials === '') {
            $initials = '?';
        }

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="128" height="128" viewBox="0 0 128 128">
  <rect width="128" height="128" rx="64" fill="#14532d"/>
  <rect width="128" height="128" rx="64" fill="rgba(255,255,255,0.16)"/>
  <text x="64" y="64" dy="0.35em" text-anchor="middle" fill="#ffffff" font-family="Inter,ui-sans-serif,system-ui,sans-serif" font-size="48" font-weight="600">{$initials}</text>
</svg>
SVG;

        return 'data:image/svg+xml;charset=utf-8,'.rawurlencode($svg);
    }
}
