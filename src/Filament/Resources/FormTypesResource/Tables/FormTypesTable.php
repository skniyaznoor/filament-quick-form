<?php

namespace FilamentQuickForm\FormBuilder\Filament\Resources\FormTypesResource\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use FilamentQuickForm\FormBuilder\Models\FormTypes;

class FormTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->sortable()->searchable(),
                TextColumn::make('slug')->sortable()->searchable(),
                TextColumn::make('status')->sortable()->badge() 
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)) 
                    ->colors([
                        'success' => 'published',
                        'warning' => 'draft',
                    ])
                    ->icons([
                        'heroicon-o-document' => 'draft',
                        'heroicon-o-check-circle' => 'published',
                    ]),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published'
                    ])
                    ->placeholder('Filter by Status')
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->hidden(fn ($record) => $record->status === 'published'),
                    ViewAction::make()
                        ->hidden(fn ($record) => $record->status === 'draft'),
                    Action::make('toggle_status')
                        ->label(fn ($record) => $record->status === 'draft' ? 'Publish' : 'Draft')
                        ->icon(fn ($record) => $record->status === 'draft' ? 'heroicon-o-check-circle' : 'heroicon-o-document')
                        ->color(fn ($record) => $record->status === 'draft' ? 'success' : 'warning')
                        ->requiresConfirmation()
                        ->modalHeading(fn ($record) => $record->status === 'draft' ? 'Publish Record' : 'Draft Record')
                        ->modalDescription(fn ($record) => $record->status === 'draft' 
                            ? 'Are you sure you want to publish this record?' 
                            : 'Are you sure you want to change this record to draft?')
                        ->modalSubmitActionLabel(fn ($record) => $record->status === 'draft' ? 'Yes, publish it' : 'Yes, draft it')
                        ->action(function ($record) {
                            if ($record->status === 'draft') {
                                $record->publish();
                            } else {
                                $record->draft();
                            }
                        }),
                    Action::make('delete')
                        ->requiresConfirmation()
                        ->color('danger')
                        ->icon('heroicon-o-trash')
                        ->hidden(fn ($record) => $record->status === 'published')
                        ->action(fn (FormTypes $record) => $record->delete()),
                ])->icon('heroicon-m-ellipsis-vertical'),
            ])         
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}