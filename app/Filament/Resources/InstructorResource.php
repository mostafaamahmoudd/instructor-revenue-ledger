<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InstructorResource\Pages;
use App\Filament\Resources\InstructorResource\RelationManagers\PayoutsRelationManager;
use App\Models\Instructor;
use App\Services\InstructorBalanceSummary;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class InstructorResource extends Resource
{
    protected static ?string $model = Instructor::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $recordTitleAttribute = 'name';

    protected static bool $shouldSkipAuthorization = true;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Instructor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('recognized_balance')
                    ->label('Recognized')
                    ->state(fn(Instructor $record): array => self::balanceList($record, 'recognized_minor'))
                    ->listWithLineBreaks(),
                Tables\Columns\TextColumn::make('paid_balance')
                    ->label('Paid')
                    ->state(fn(Instructor $record): array => self::balanceList($record, 'paid_minor'))
                    ->listWithLineBreaks(),
                Tables\Columns\TextColumn::make('available_balance')
                    ->label('Available')
                    ->state(fn(Instructor $record): array => self::balanceList($record, 'available_minor'))
                    ->listWithLineBreaks(),
                Tables\Columns\TextColumn::make('reserved_balance')
                    ->label('Reserved')
                    ->state(fn(Instructor $record): array => self::balanceList($record, 'reserved_minor'))
                    ->listWithLineBreaks(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Instructor')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('email'),
                    ]),
                Section::make('Balances')
                    ->columns(5)
                    ->schema([
                        TextEntry::make('recognized_balance')
                            ->label('Recognized earnings')
                            ->state(fn(Instructor $record): array => self::balanceList($record, 'recognized_minor'))
                            ->listWithLineBreaks(),
                        TextEntry::make('paid_balance')
                            ->label('Paid')
                            ->state(fn(Instructor $record): array => self::balanceList($record, 'paid_minor'))
                            ->listWithLineBreaks(),
                        TextEntry::make('available_balance')
                            ->label('Available for payout')
                            ->state(fn(Instructor $record): array => self::balanceList($record, 'available_minor'))
                            ->listWithLineBreaks(),
                        TextEntry::make('reserved_balance')
                            ->label('Reserved / in payout')
                            ->state(fn(Instructor $record): array => self::balanceList($record, 'reserved_minor'))
                            ->listWithLineBreaks(),
                        TextEntry::make('outstanding_balance')
                            ->label('Total outstanding')
                            ->state(fn(Instructor $record): array => self::balanceList($record, 'outstanding_minor'))
                            ->listWithLineBreaks(),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PayoutsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInstructors::route('/'),
            'view' => Pages\ViewInstructor::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    private static function balanceList(Instructor $record, string $key): array
    {
        return app(InstructorBalanceSummary::class)->formatList(
            app(InstructorBalanceSummary::class)->forInstructor($record),
            $key
        );
    }
}
