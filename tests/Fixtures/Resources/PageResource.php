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
use Mansoor\FilamentVersionable\Tests\Fixtures\Models\Page;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')->required(),
                TextInput::make('slug')->required(),
                Textarea::make('content'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title'),
                TextColumn::make('slug'),
            ])
            ->recordActions([
                EditAction::make(),
                RevisionsAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => PageResource\Pages\ListPages::route('/'),
            'create' => PageResource\Pages\CreatePage::route('/create'),
            'edit' => PageResource\Pages\EditPage::route('/{record}/edit'),
            'revisions' => PageResource\Pages\PageRevisions::route('/{record}/revisions'),
        ];
    }
}
