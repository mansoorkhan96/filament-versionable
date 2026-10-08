<?php

namespace Mansoor\FilamentVersionable\Tests\Fixtures\Resources;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Mansoor\FilamentVersionable\Table\RevisionsAction;
use Mansoor\FilamentVersionable\Tests\Fixtures\Models\VersionablePost;

class VersionablePostResource extends Resource
{
    protected static ?string $model = VersionablePost::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')->required(),
                Textarea::make('content'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title'),
                TextColumn::make('content'),
            ])
            ->recordActions([
                RevisionsAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => VersionablePostResource\Pages\ListVersionablePosts::route('/'),
            'create' => VersionablePostResource\Pages\CreateVersionablePost::route('/create'),
            'edit' => VersionablePostResource\Pages\EditVersionablePost::route('/{record}/edit'),
            'revisions' => VersionablePostResource\Pages\VersionablePostRevisions::route('/{record}/revisions'),
        ];
    }
}
