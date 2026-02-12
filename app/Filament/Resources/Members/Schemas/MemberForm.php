<?php

namespace App\Filament\Resources\Members\Schemas;

use App\Models\Branch;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class MemberForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                /**
                 * =========================
                 * Basic Information
                 * =========================
                 */
                Section::make('Basic Information')
                    ->description('Core member details and branch assignment')
                    ->icon('heroicon-m-archive-box')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([

                        TextInput::make('code')
                            ->label('System Code')
                            ->placeholder('MBR-00001')
                            ->maxLength(50)
                            ->helperText('System-generated code (not editable)')
                            ->readOnly()
                            ->default(fn () => 'MBR-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT)),

                        TextInput::make('cid')
                            ->label('CID')
                            ->placeholder('Custom ID')
                            ->maxLength(50)
                            ->helperText('Optional: Custom identification code for the member'),

                        Select::make('branch_number')
                            ->label('Branch')
                            ->placeholder('Select a branch')
                            ->options(function () {
                                return Cache::remember('branches_options', 3600, function () {
                                    // Pluck branch name as label, id as value
                                    return Branch::query()->pluck('branch_name', 'branch_number')->toArray();
                                });
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Select the branch this member belongs to')
                            ->default(function () {
                                $user = Auth::user();
                                return $user->branch?->branch_number ?? null;
                            }),
                    ]),

                /**
                 * =========================
                 * Personal Details
                 * =========================
                 */
                Section::make('Personal Details')
                    ->description('Basic personal information of the member')
                    ->icon('heroicon-m-user')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([

                        TextInput::make('first_name')
                            ->label('First Name')
                            ->required()
                            ->maxLength(100)
                            ->helperText('Enter the member’s first name'),

                        TextInput::make('middle_name')
                            ->label('Middle Name')
                            ->maxLength(100)
                            ->placeholder('Optional')
                            ->helperText('Optional'),

                        TextInput::make('last_name')
                            ->label('Last Name')
                            ->required()
                            ->maxLength(100)
                            ->helperText('Enter the member’s last name'),

                        DatePicker::make('birth_date')
                            ->label('Birth Date')
                            ->native(false)
                            ->required()
                            ->maxDate(now())
                            ->helperText('Select the member’s date of birth'),

                        Select::make('gender')
                            ->label('Gender')
                            ->options([
                                'Male' => 'Male',
                                'Female' => 'Female',
                                'Other' => 'Other',
                            ])
                            ->placeholder('Select gender')
                            ->helperText('Choose the gender of the member'),

                        Select::make('marital_status')
                            ->label('Marital Status')
                            ->options([
                                'Single' => 'Single',
                                'Married' => 'Married',
                                'Separated' => 'Separated',
                                'Widowed' => 'Widowed',
                            ])
                            ->placeholder('Select marital status')
                            ->helperText('Current marital status of the member'),
                    ]),

                /**
                 * =========================
                 * Contact Information
                 * =========================
                 */
                Section::make('Contact Information')
                    ->description('How we can reach the member')
                    ->icon('heroicon-m-phone')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([

                        TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->maxLength(255)
                            ->placeholder('name@example.com')
                            ->helperText('Must be unique if provided')
                            ->unique(
                                table: 'members',
                                column: 'email',
                                ignorable: fn ($record) => $record
                            ),

                        TextInput::make('contact_number')
                            ->label('Contact Number')
                            ->tel()
                            ->placeholder('09XXXXXXXXX')
                            ->helperText('Primary mobile or phone number')
                            ->maxLength(20),

                        TextInput::make('address')
                            ->label('Address')
                            ->columnSpanFull()
                            ->placeholder('House number, street, barangay, city, province')
                            ->helperText('Complete residential address'),

                        TextInput::make('occupation')
                            ->label('Occupation')
                            ->placeholder('Current occupation')
                            ->helperText('Optional'),

                        TextInput::make('religion')
                            ->label('Religion')
                            ->placeholder('Optional')
                            ->helperText('Optional'),
                    ]),

                /**
                 * =========================
                 * Membership & Shares
                 * =========================
                 */
                Section::make('Membership Details')
                    ->description('Share account and membership information')
                    ->icon('heroicon-m-banknotes')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([

                        TextInput::make('share_account')
                            ->label('Share Account No.')
                            ->placeholder('SA-XXXXXXX')
                            ->helperText('Required for members with share accounts')
                            ->maxLength(50),

                        Select::make('share_amount')
                            ->label('Share Amount')
                            ->options([
                                '0' => 'Below 3,000',
                                '1' => '3,000 and Above',
                            ])
                            ->placeholder('Select share amount range')
                            ->helperText('Choose the member’s share contribution range')
                            ->required(),

                        DatePicker::make('membership_date')
                            ->label('Membership Date')
                            ->native(false)
                            ->helperText('Date the member officially joined')
                            ->maxDate(now()),
                    ]),

                /**
                 * =========================
                 * Registration & Status
                 * =========================
                 */
                Section::make('Status & Registration')
                    ->columnSpanFull()
                    ->description('Control member status and registration settings')
                    ->icon('heroicon-m-adjustments-horizontal')
                    ->columns(3)
                    ->schema([

                        Toggle::make('is_active')
                            ->label('Active Member')
                            ->helperText('Turn off to deactivate this member')
                            ->default(true)
                            ->onColor('success')
                            ->offColor('danger')
                            ->onIcon('heroicon-m-check-circle')
                            ->offIcon('heroicon-m-x-circle'),

                        Toggle::make('is_registered')
                            ->label('Registered')
                            ->helperText('Indicates if the member has completed registration')
                            ->default(true)
                            ->reactive()
                            ->onColor('success')
                            ->offColor('warning')
                            ->onIcon('heroicon-m-shield-check')
                            ->offIcon('heroicon-m-shield-exclamation'),

                        Toggle::make('is_migs')
                            ->label('MIGS Member')
                            ->helperText('Enable if the member is enrolled in MIGS')
                            ->default(false)
                            ->onColor('info')
                            ->onIcon('heroicon-m-banknotes')
                            ->offIcon('heroicon-m-minus-circle'),

                        /**
                         * Process Type
                         * ON  = Updating and Voting
                         * OFF = Updating Only
                         */
                        Toggle::make('process_type')
                            ->label('Allow Voting')
                            ->helperText('Enable to allow voting during updates')
                            ->default(true)
                            ->reactive()
                            ->onColor('primary')
                            ->onIcon('heroicon-m-check-badge')
                            ->offIcon('heroicon-m-pencil-square')
                            ->afterStateHydrated(function (Toggle $component, $state) {
                                $component->state($state === 'Updating and Voting');
                            })
                            ->dehydrateStateUsing(fn (bool $state) =>
                                $state ? 'Updating and Voting' : 'Updating Only'
                            )
                            ->disabled(fn (callable $get) => ! $get('is_registered')),

                        /**
                         * Registration Type
                         * ON  = Online
                         * OFF = On-premise
                         */
                        Toggle::make('registration_type')
                            ->label('Online Registration')
                            ->helperText('Disable if registration was done on-premise')
                            ->default(true)
                            ->onColor('success')
                            ->onIcon('heroicon-m-globe-alt')
                            ->offIcon('heroicon-m-building-office')
                            ->afterStateHydrated(function (Toggle $component, $state) {
                                $component->state($state === 'Online');
                            })
                            ->dehydrateStateUsing(fn (bool $state) =>
                                $state ? 'Online' : 'On-premise'
                            )
                            ->disabled(fn (callable $get) => ! $get('is_registered')),
                    ]),
            ]);
    }
}
