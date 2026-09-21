<?php

namespace App\Filament\AvatarProviders;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Initials placeholder when a record has no uploaded profile image.
 * Background color is unique per record (stable hash), with white initials.
 */
class GreenTintAvatarProvider implements AvatarProvider
{
    /**
     * Harmonious mid-tones that keep white initials readable (WCAG AA).
     * Indices map to `.m2b-avatar-bg-{n}` in theme.css for table cells
     * (Filament sanitizes data-URI <img> tags in TextColumn HTML).
     *
     * @var list<string>
     */
    public const BACKGROUNDS = [
        '#14532d', // 0 brand green
        '#047857', // 1 emerald
        '#0f766e', // 2 teal
        '#0e7490', // 3 cyan
        '#0369a1', // 4 sky
        '#1d4ed8', // 5 blue
        '#4338ca', // 6 indigo
        '#6d28d9', // 7 violet
        '#be185d', // 8 pink
        '#b91c1c', // 9 red
        '#c2410c', // 10 orange
        '#b45309', // 11 amber
        '#4d7c0f', // 12 lime
        '#334155', // 13 slate
        '#3f6212', // 14 olive
        '#115e59', // 15 deep teal
    ];

    public function get(Model | Authenticatable $record): string
    {
        $name = (string) Filament::getNameForDefaultAvatar($record);
        $initials = self::initialsFromName($name);
        // Logged-in / user avatars stay brand green; other models keep unique colors.
        $background = $record instanceof \App\Models\User
            ? '#14532d'
            : self::BACKGROUNDS[self::backgroundIndexFor($record, $name)];
        $initialsXml = htmlspecialchars($initials, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="128" height="128" viewBox="0 0 128 128">
  <rect width="128" height="128" rx="64" fill="{$background}"/>
  <rect width="128" height="128" rx="64" fill="rgba(255,255,255,0.12)"/>
  <text x="64" y="64" dy="0.35em" text-anchor="middle" fill="#ffffff" font-family="Inter,ui-sans-serif,system-ui,sans-serif" font-size="48" font-weight="600">{$initialsXml}</text>
</svg>
SVG;

        // Base64 is reliable in <img> (topbar). Do not use URL-encoded data URIs in
        // Filament TextColumn HTML — sanitizeHtml strips them.
        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /**
     * @return array{initials: string, background_index: int, background: string}
     */
    public static function placeholder(Model | Authenticatable $record, ?string $name = null): array
    {
        $name ??= (string) Filament::getNameForDefaultAvatar($record);
        $isUser = $record instanceof \App\Models\User;
        $index = $isUser ? 0 : self::backgroundIndexFor($record, $name);

        return [
            'initials' => self::initialsFromName($name),
            'background_index' => $index,
            'background' => $isUser ? '#14532d' : self::BACKGROUNDS[$index],
        ];
    }

    public static function initialsFromName(string $name): string
    {
        $initials = str($name)
            ->trim()
            ->explode(' ')
            ->map(fn (string $segment): string => filled($segment) ? mb_strtoupper(mb_substr($segment, 0, 1)) : '')
            ->join('');

        $initials = mb_substr((string) $initials, 0, 2);

        return $initials !== '' ? $initials : '?';
    }

    public static function backgroundIndexFor(Model | Authenticatable $record, string $name): int
    {
        $id = method_exists($record, 'getAuthIdentifier')
            ? (string) $record->getAuthIdentifier()
            : (string) ($record instanceof Model ? $record->getKey() : '');

        $seed = implode(':', [
            $record::class,
            $id,
            mb_strtolower(trim($name)),
        ]);

        return abs((int) crc32($seed)) % count(self::BACKGROUNDS);
    }
}
