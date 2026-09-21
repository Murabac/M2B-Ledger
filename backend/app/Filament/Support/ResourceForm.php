<?php

namespace App\Filament\Support;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\Section;

class ResourceForm
{
    /**
     * Full-width section card: title + description in the card header, fields in a responsive grid.
     *
     * @param  array<Component>  $schema
     */
    public static function section(
        string $title,
        string $description,
        array $schema,
        int $columns = 2,
    ): Section {
        return Section::make($title)
            ->description($description)
            ->columns([
                'default' => 1,
                'md' => $columns,
            ])
            ->schema($schema);
    }
}
