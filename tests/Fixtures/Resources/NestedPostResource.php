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
use Mansoor\FilamentVersionable\Tests\Fixtures\Models\Post;

class NestedPostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $parentResource = CategoryResource::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $slug = 'posts';

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
                EditAction::make(),
                RevisionsAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => NestedPostResource\Pages\ListNestedPosts::route('/'),
            'create' => NestedPostResource\Pages\CreateNestedPost::route('/create'),
            'edit' => NestedPostResource\Pages\EditNestedPost::route('/{record}/edit'),
            'revisions' => NestedPostResource\Pages\NestedPostRevisions::route('/{record}/revisions'),
        ];
    }
}
