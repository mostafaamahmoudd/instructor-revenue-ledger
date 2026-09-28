<?php

namespace App\Filament\Resources\InstructorResource\RelationManagers;

use App\Models\Payout;
use App\Services\InstructorBalanceSummary;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PayoutsRelationManager extends RelationManager
{
    protected static string $relationship = 'payouts';

    protected static ?string $title = 'Payout history';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('period_key')
            ->modifyQueryUsing(fn(Builder $query): Builder => $query->latest('created_at'))
            ->columns([
                Tables\Columns\TextColumn::make('period_key')
                    ->label('Period')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount_minor')
                    ->label('Amount')
                    ->state(fn(Payout $record): string => app(InstructorBalanceSummary::class)->formatMinor($record->amount_minor, $record->currency))
                    ->sortable(),
                Tables\Columns\TextColumn::make('currency')
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        Payout::STATUS_SUCCEEDED => 'success',
                        Payout::STATUS_FAILED => 'danger',
                        Payout::STATUS_UNCERTAIN => 'warning',
                        Payout::STATUS_PROCESSING => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('requires_manual_review')
                    ->label('Review')
                    ->boolean(),
                Tables\Columns\TextColumn::make('attempts')
                    ->sortable(),
                Tables\Columns\TextColumn::make('provider_reference')
                    ->label('Provider reference')
                    ->placeholder('-')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Processed / updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
