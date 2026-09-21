<?php

namespace App\Filament\Support;

use App\Filament\AvatarProviders\GreenTintAvatarProvider;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class IdentityColumn
{
    /**
     * Name (medium) + gray description, with initials avatar (unique color per record).
     *
     * Uses a <span> + CSS class (not data-URI <img>) because Filament TextColumn
     * runs Str::sanitizeHtml() which strips data: image sources.
     */
    public static function make(
        string $nameAttribute = 'name',
        string $descriptionAttribute = 'email',
        string $label = 'User',
    ): TextColumn {
        return TextColumn::make($nameAttribute)
            ->label($label)
            ->searchable([$nameAttribute, $descriptionAttribute])
            ->sortable()
            ->weight(FontWeight::Medium)
            ->html()
            ->formatStateUsing(function (mixed $state, Model $record) use ($nameAttribute, $descriptionAttribute): HtmlString {
                $name = (string) data_get($record, $nameAttribute, $state ?? '');
                $description = (string) data_get($record, $descriptionAttribute, '');

                return new HtmlString(self::renderIdentity($record, $name, $description));
            });
    }

    /**
     * Single-line identity (e.g. company name) with initials avatar, no description.
     */
    public static function named(
        string $nameAttribute = 'name',
        string $label = 'Name',
    ): TextColumn {
        return TextColumn::make($nameAttribute)
            ->label($label)
            ->searchable()
            ->sortable()
            ->weight(FontWeight::Medium)
            ->html()
            ->formatStateUsing(function (mixed $state, Model $record) use ($nameAttribute): HtmlString {
                $name = (string) data_get($record, $nameAttribute, $state ?? '');

                return new HtmlString(self::renderIdentity($record, $name));
            });
    }

    private static function renderIdentity(Model $record, string $name, string $description = ''): string
    {
        $placeholder = GreenTintAvatarProvider::placeholder($record, $name);
        $initials = e($placeholder['initials']);
        $bgClass = $record instanceof \App\Models\User
            ? 'm2b-avatar-user'
            : 'm2b-avatar-bg-'.$placeholder['background_index'];
        $nameHtml = e($name);
        $descHtml = $description !== ''
            ? '<span class="m2b-identity-desc">'.e($description).'</span>'
            : '';

        return <<<HTML
<div class="m2b-identity">
    <span class="m2b-identity-avatar {$bgClass}" aria-hidden="true">{$initials}</span>
    <div class="m2b-identity-text">
        <span class="m2b-identity-name">{$nameHtml}</span>
        {$descHtml}
    </div>
</div>
HTML;
    }
}
