<?php

namespace Mansoor\FilamentVersionable\Tests\Fixtures\Resources;

use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Mansoor\FilamentVersionable\Table\RevisionsAction;
use Mansoor\FilamentVersionable\Tests\Fixtures\Models\TranslatablePost;

class TranslatablePostResource extends Resource
{
    protected static ?string $model = TranslatablePost::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-language';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')->required(),
                Textarea::make('summary'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title'),
                TextColumn::make('status'),
            ])
            ->recordActions([
                EditAction::make(),
                RevisionsAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => TranslatablePostResource\Pages\ListTranslatablePosts::route('/'),
            'create' => TranslatablePostResource\Pages\CreateTranslatablePost::route('/create'),
            'edit' => TranslatablePostResource\Pages\EditTranslatablePost::route('/{record}/edit'),
            'revisions' => TranslatablePostResource\Pages\TranslatablePostRevisions::route('/{record}/revisions'),
        ];
    }
}
