<?php

namespace App\Filament\Resources\Instructors;

use App\Filament\Resources\Instructors\Pages\ManageInstructors;
use App\Models\Instructor;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Tables;

class InstructorResource extends Resource
{
    protected static ?string $model = Instructor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
        
        ->columns([
            Tables\Columns\TextColumn::make('name')->searchable(),
            Tables\Columns\TextColumn::make('earned_cents')
                ->label('Total Earned')
                ->money('USD', divideBy: 100)
                ->getStateUsing(fn ($record) => $record->ledgerEntries()->where('type', 'credit')->sum('amount_cents')),
            Tables\Columns\TextColumn::make('paid_cents')
                ->label('Total Paid Out')
                ->money('USD', divideBy: 100)
                ->getStateUsing(fn ($record) => $record->ledgerEntries()->where('type', 'debit')->sum('amount_cents')),
            Tables\Columns\TextColumn::make('current_balance')
                ->label('Outstanding Balance')
                ->money('USD', divideBy: 100)
                ->getStateUsing(fn ($record) => 
                    $record->ledgerEntries()->where('type', 'credit')->sum('amount_cents') -
                    $record->ledgerEntries()->where('type', 'debit')->sum('amount_cents')
                ),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageInstructors::route('/'),
        ];
    }
}
