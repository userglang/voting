<?php

namespace App\Filament\Resources\Candidates\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CandidateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Position Section
                Section::make('Candidate Information')
                    ->description('Provide the basic information of the candidate.')
                    ->schema([
                        // Position Select (Dropdown)
                        Select::make('position_id')
                            ->label('Position')
                            ->relationship('position', 'title')
                            ->required()
                            ->preload()
                            ->placeholder('Select the candidate\'s position')
                            ->searchable()
                            ->hint('Select a position from the list'),

                        // First Name
                        TextInput::make('first_name')
                            ->label('First Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Enter the first name')
                            ->hint('Candidate\'s first name is mandatory'),

                        // Last Name
                        TextInput::make('last_name')
                            ->label('Last Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Enter the last name')
                            ->hint('Candidate\'s last name is mandatory'),

                        // Middle Name (Optional)
                        TextInput::make('middle_name')
                            ->label('Middle Name (Optional)')
                            ->nullable()
                            ->maxLength(255)
                            ->placeholder('Enter middle name (if any)'),

                        // Background Profile (Optional)
                        MarkdownEditor::make('background_profile')
                            ->label('Candidate Background (Optional)')
                            ->nullable()
                            ->toolbarButtons([
                                'bold', 'italic', 'strike', 'link',
                                'heading', 'blockquote', 'codeBlock',
                                'bulletList', 'orderedList', 'table', 'attachFiles',
                                'undo', 'redo'
                            ])
                            ->helperText('Provide a brief background or profile for the candidate.')
                            ->placeholder('Brief candidate background or profile'),
                    ]),

                // Image Section
                Section::make('Profile Image')
                    ->description('Upload the candidate\'s profile image.')
                    ->schema([
                        FileUpload::make('image')
                            ->label('Profile Image')
                            ->image()
                            ->disk('public')
                            ->directory('candidates/images')
                            ->maxSize(2048)
                            ->imageEditor()
                            ->afterStateHydrated(function ($component, $state) {
                                if ($component && $state && !str_contains($state, '/')) {
                                    $component->state('candidates/images/' . $state);
                                }
                            })
                            ->dehydrateStateUsing(fn ($state) => $state ? basename($state) : null)
                            ->helperText('Upload a JPEG or PNG image (max 2MB).'),
                    ]),
            ]);
    }
}
