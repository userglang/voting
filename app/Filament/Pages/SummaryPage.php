<?php

namespace App\Filament\Pages;

use App\Exports\MembersExport;
use App\Exports\MembersSummaryExport;
use App\Models\Branch;
use App\Models\Member;
use App\Models\Vote;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;
use UnitEnum;

class SummaryPage extends Page implements HasTable
{
    use InteractsWithTable;


    protected static string | BackedEnum | null $navigationIcon = Heroicon::ClipboardDocumentCheck;
    protected static ?string $title = 'Summary';
    protected static ?string $navigationLabel = 'Summary';
    protected string  $view            = 'filament.pages.summary-page';

    protected static string | UnitEnum | null $navigationGroup = 'Reports';
    protected static ?int $navigationSort = 3;


    protected function getHeaderActions(): array
    {
        return [

            ActionGroup::make([
                // Export to Excel - Detailed
                Action::make('exportExcel')
                    ->label('Export to Excel (Detailed)')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->form([
                        Section::make('Export Filters')
                            ->description('Filter the data to export')
                            ->schema([
                                Select::make('branch_number')
                                    ->label('Branch')
                                    ->options(\App\Models\Branch::pluck('branch_name', 'branch_number'))
                                    ->searchable()
                                    ->placeholder('All Branches'),

                                Radio::make('is_active')
                                    ->label('Status')
                                    ->options([
                                        'all' => 'All Members',
                                        'active' => 'Active Only',
                                        'inactive' => 'Inactive Only',
                                    ])
                                    ->default('all')
                                    ->inline(),

                                Radio::make('is_migs')
                                    ->label('MIGS Membership')
                                    ->options([
                                        'all' => 'All Members',
                                        'yes' => 'MIGS Members Only',
                                        'no' => 'Non-MIGS Only',
                                    ])
                                    ->default('all')
                                    ->inline(),

                                Radio::make('is_registered')
                                    ->label('Registration Status')
                                    ->options([
                                        'all' => 'All Members',
                                        'registered' => 'Registered Only',
                                        'not_registered' => 'Not Registered Only',
                                    ])
                                    ->default('all')
                                    ->inline(),

                                Select::make('gender')
                                    ->label('Gender')
                                    ->options([
                                        'Male' => 'Male',
                                        'Female' => 'Female',
                                    ])
                                    ->placeholder('All Genders'),
                            ])
                            ->columns(2),
                    ])
                    ->action(function (array $data) {
                        try {
                            $filename = 'members-detailed-' . now()->format('Y-m-d-His') . '.xlsx';

                            return Excel::download(
                                new MembersExport($data),
                                $filename
                            );
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Export Failed')
                                ->body('Error: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // Export to Excel - Summary
                Action::make('exportSummary')
                    ->label('Export to Excel (Summary)')
                    ->icon('heroicon-o-chart-bar')
                    ->color('info')
                    ->form([
                        Section::make('Export Filters')
                            ->description('Filter the summary data')
                            ->schema([
                                Select::make('branch_number')
                                    ->label('Branch')
                                    ->options(\App\Models\Branch::pluck('branch_name', 'branch_number'))
                                    ->searchable()
                                    ->placeholder('All Branches'),
                            ])
                            ->columns(1),
                    ])
                    ->action(function (array $data) {
                        try {
                            $filename = 'members-summary-' . now()->format('Y-m-d-His') . '.xlsx';

                            return Excel::download(
                                new MembersSummaryExport($data),
                                $filename
                            );
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Export Failed')
                                ->body('Error: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // Export to PDF - Detailed
                Action::make('exportPdf')
                    ->label('Export to PDF (Detailed)')
                    ->icon('heroicon-o-document-text')
                    ->color('danger')
                    ->form([
                        Section::make('Export Filters')
                            ->description('Filter the data to export')
                            ->schema([
                                Select::make('branch_number')
                                    ->label('Branch')
                                    ->options(\App\Models\Branch::pluck('branch_name', 'branch_number'))
                                    ->searchable()
                                    ->placeholder('All Branches'),

                                Radio::make('is_active')
                                    ->label('Status')
                                    ->options([
                                        'all' => 'All Members',
                                        'active' => 'Active Only',
                                        'inactive' => 'Inactive Only',
                                    ])
                                    ->default('all')
                                    ->inline(),

                                Radio::make('is_migs')
                                    ->label('MIGS Membership')
                                    ->options([
                                        'all' => 'All Members',
                                        'yes' => 'MIGS Members Only',
                                        'no' => 'Non-MIGS Only',
                                    ])
                                    ->default('all')
                                    ->inline(),

                                Select::make('gender')
                                    ->label('Gender')
                                    ->options([
                                        'Male' => 'Male',
                                        'Female' => 'Female',
                                    ])
                                    ->placeholder('All Genders'),
                            ])
                            ->columns(2),
                    ])
                    ->action(function (array $data) {

                        try {
                            $query = \App\Models\Member::query()
                                ->with('branch')
                                ->orderBy('branch_number')
                                ->orderBy('last_name');

                            // Filters
                            if (!empty($data['branch_number'])) {
                                $query->where('branch_number', $data['branch_number']);
                            }

                            if (($data['is_active'] ?? 'all') === 'active') {
                                $query->where('is_active', true);
                            } elseif (($data['is_active'] ?? 'all') === 'inactive') {
                                $query->where('is_active', false);
                            }

                            if (($data['is_migs'] ?? 'all') === 'yes') {
                                $query->where('is_migs', true);
                            } elseif (($data['is_migs'] ?? 'all') === 'no') {
                                $query->where('is_migs', false);
                            }

                            if (!empty($data['gender'])) {
                                $query->where('gender', $data['gender']);
                            }

                            $members = $query->get();

                            /* =======================
                            STATISTICS
                            ======================== */

                            $totalMembers = $members->count();

                            // Gender totals
                            $totalMale = $members->where('gender', 'Male')->count();
                            $totalFemale = $members->where('gender', 'Female')->count();

                            // Registered by gender
                            $registeredMale = $members->where('gender', 'Male')
                                ->where('is_registered', true)
                                ->count();

                            $registeredFemale = $members->where('gender', 'Female')
                                ->where('is_registered', true)
                                ->count();

                            // MIGS totals
                            $totalMigs = $members->where('is_migs', true)->count();
                            $totalNonMigs = $members->where('is_migs', false)->count();

                            // Registered MIGS
                            $registeredMigs = $members->where('is_migs', true)
                                ->where('is_registered', true)
                                ->count();

                            $registeredNonMigs = $members->where('is_migs', false)
                                ->where('is_registered', true)
                                ->count();

                            /* =======================
                            FILTER LABELS
                            ======================== */

                            $filters = [
                                'branch_name' => !empty($data['branch_number'])
                                    ? \App\Models\Branch::where('branch_number', $data['branch_number'])->first()?->branch_name
                                    : 'All Branches',

                                'status_label' => match ($data['is_active'] ?? 'all') {
                                    'active' => 'Active Only',
                                    'inactive' => 'Inactive Only',
                                    default => 'All Statuses',
                                },

                                'migs_label' => match ($data['is_migs'] ?? 'all') {
                                    'yes' => 'MIGS Only',
                                    'no' => 'Non-MIGS Only',
                                    default => 'All Members',
                                },

                                'gender' => $data['gender'] ?? 'All Genders',
                            ];

                            $pdf = Pdf::loadView('pdf.members-report', [
                                'members' => $members,
                                'filters' => $filters,

                                'totalMembers' => $totalMembers,

                                'totalMale' => $totalMale,
                                'totalFemale' => $totalFemale,

                                'registeredMale' => $registeredMale,
                                'registeredFemale' => $registeredFemale,

                                'totalMigs' => $totalMigs,
                                'totalNonMigs' => $totalNonMigs,

                                'registeredMigs' => $registeredMigs,
                                'registeredNonMigs' => $registeredNonMigs,
                            ])->setPaper('a4', 'portrait');

                            return response()->streamDownload(
                                fn () => print($pdf->output()),
                                'members-report-' . now()->format('Y-m-d-His') . '.pdf'
                            );

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Export Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),


                // Export to PDF - Summary
                Action::make('exportPdfSummary')
                    ->label('Export to PDF (Summary)')
                    ->icon('heroicon-o-chart-bar-square')
                    ->color('warning')
                    ->form([
                        Section::make('Export Filters')
                            ->description('Filter the summary data')
                            ->schema([
                                Select::make('branch_number')
                                    ->label('Branch')
                                    ->options(\App\Models\Branch::pluck('branch_name', 'branch_number'))
                                    ->searchable()
                                    ->placeholder('All Branches'),
                            ])
                            ->columns(1),
                    ])
                    ->action(function (array $data) {
                        try {
                            $branchQuery = \App\Models\Branch::query()->where('is_active', true);

                            if (!empty($data['branch_number'])) {
                                $branchQuery->where('branch_number', $data['branch_number']);
                            }

                            $branches = $branchQuery->orderBy('branch_name')->get();

                            $summary = $branches->map(function ($branch) {
                                $memberQuery = \App\Models\Member::where('branch_number', $branch->branch_number);

                                $totalMembers = $memberQuery->count();
                                $totalMigs = (clone $memberQuery)->where('is_migs', true)->count();
                                $totalNonMigs = (clone $memberQuery)->where('is_migs', false)->count();

                                $totalRegMigs = (clone $memberQuery)
                                    ->where('is_migs', true)
                                    ->where('is_registered', true)
                                    ->count();

                                $totalRegNonMigs = (clone $memberQuery)
                                    ->where('is_migs', false)
                                    ->where('is_registered', true)
                                    ->count();

                                $quorumPercentage = $totalMigs > 0
                                    ? ($totalRegMigs / $totalMigs) * 100
                                    : 0;

                                $totalCastedVotes = \App\Models\Vote::where('branch_number', $branch->branch_number)->count();

                                return [
                                    'branch_number' => $branch->branch_number,
                                    'branch_name' => $branch->branch_name,
                                    'total_members' => $totalMembers,
                                    'total_migs' => $totalMigs,
                                    'total_non_migs' => $totalNonMigs,
                                    'total_reg_migs' => $totalRegMigs,
                                    'total_reg_non_migs' => $totalRegNonMigs,
                                    'quorum_percentage' => $quorumPercentage,
                                    'total_casted_votes' => $totalCastedVotes,
                                ];
                            });

                            // Calculate grand totals
                            $totalMembers = $summary->sum('total_members');
                            $totalBranches = $summary->count();
                            $totalMigs = $summary->sum('total_migs');
                            $totalNonMigs = $summary->sum('total_non_migs');
                            $totalRegMigs = $summary->sum('total_reg_migs');
                            $totalRegNonMigs = $summary->sum('total_reg_non_migs');
                            $totalRegistered = $totalRegMigs + $totalRegNonMigs;
                            $totalVotes = $summary->sum('total_casted_votes');
                            $overallQuorum = $totalMigs > 0 ? ($totalRegMigs / $totalMigs) * 100 : 0;

                            $pdf = Pdf::loadView('pdf.members-summary', [
                                'summary' => $summary,
                                'totalMembers' => $totalMembers,
                                'totalBranches' => $totalBranches,
                                'totalMigs' => $totalMigs,
                                'totalNonMigs' => $totalNonMigs,
                                'totalRegMigs' => $totalRegMigs,
                                'totalRegNonMigs' => $totalRegNonMigs,
                                'totalRegistered' => $totalRegistered,
                                'totalVotes' => $totalVotes,
                                'overallQuorum' => $overallQuorum,
                            ])->setPaper('a4', 'portrait');

                            $filename = 'members-summary-' . now()->format('Y-m-d-His') . '.pdf';

                            return response()->streamDownload(function () use ($pdf) {
                                echo $pdf->output();
                            }, $filename);

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Export Failed')
                                ->body('Error: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
                ->label('Export Reports')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->button(),

        ];
    }

    /**
     * Get overall summary statistics.
     */
    public function getOverallSummaryProperty(): array
    {
        $totalMembers = Member::where('is_active', true)->count();
        $totalMigs    = Member::where('is_active', true)->where('is_migs', true)->count();
        $totalNonMigs = Member::where('is_active', true)->where('is_migs', false)->count();

        $totalRegisteredMigs    = Member::where('is_active', true)
            ->where('is_migs', true)
            ->where('is_registered', true)
            ->count();

        $totalRegisteredNonMigs = Member::where('is_active', true)
            ->where('is_migs', false)
            ->where('is_registered', true)
            ->count();

        $totalRegistered = $totalRegisteredMigs + $totalRegisteredNonMigs;

        // Total unique voters (members who have cast at least one vote)
        $totalVoters = Vote::distinct('member_code')->count('member_code');

        // Quorum percentage: (Total Registered MIGS / Total MIGS) * 100
        $quorumPercentage = $totalMigs > 0
            ? round(($totalRegisteredMigs / $totalMigs) * 100, 2)
            : 0;

        // Total casted votes (total vote records)
        $totalCastedVotes = Vote::count();

        return [
            'total_members'            => $totalMembers,
            'total_migs'               => $totalMigs,
            'total_non_migs'           => $totalNonMigs,
            'total_registered_migs'    => $totalRegisteredMigs,
            'total_registered_non_migs'=> $totalRegisteredNonMigs,
            'total_registered'         => $totalRegistered,
            'total_voters'             => $totalVoters,
            'quorum_percentage'        => $quorumPercentage,
            'total_casted_votes'       => $totalCastedVotes,
        ];
    }

    /**
     * Filament Table — branch breakdown.
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(Branch::query()->where('is_active', true)->orderBy('branch_name'))
            ->columns([

                // Branch Name
                TextColumn::make('branch_name')
                    ->label('Branch')
                    ->searchable()
                    ->sortable(),

                // Total Members
                TextColumn::make('total_members')
                    ->label('Total Members')
                    ->alignCenter()
                    ->weight('semibold')
                    ->getStateUsing(fn (Branch $record): int =>
                        Member::where('branch_number', $record->branch_number)
                            ->where('is_active', true)
                            ->count()
                    ),

                // Total MIGS
                TextColumn::make('total_migs')
                    ->label('Total MIGS')
                    ->alignCenter()
                    ->badge()
                    ->color('success')
                    ->getStateUsing(fn (Branch $record): int =>
                        Member::where('branch_number', $record->branch_number)
                            ->where('is_active', true)
                            ->where('is_migs', true)
                            ->count()
                    ),

                // Total Non-MIGS
                TextColumn::make('total_non_migs')
                    ->label('Total Non-MIGS')
                    ->alignCenter()
                    ->badge()
                    ->color('warning')
                    ->getStateUsing(fn (Branch $record): int =>
                        Member::where('branch_number', $record->branch_number)
                            ->where('is_active', true)
                            ->where('is_migs', false)
                            ->count()
                    ),

                // Registered MIGS
                TextColumn::make('registered_migs')
                    ->label('Registered MIGS')
                    ->alignCenter()
                    ->badge()
                    ->color('success')
                    ->getStateUsing(fn (Branch $record): int =>
                        Member::where('branch_number', $record->branch_number)
                            ->where('is_active', true)
                            ->where('is_migs', true)
                            ->where('is_registered', true)
                            ->count()
                    ),

                // Registered Non-MIGS
                TextColumn::make('registered_non_migs')
                    ->label('Registered Non-MIGS')
                    ->alignCenter()
                    ->badge()
                    ->color('warning')
                    ->getStateUsing(fn (Branch $record): int =>
                        Member::where('branch_number', $record->branch_number)
                            ->where('is_active', true)
                            ->where('is_migs', false)
                            ->where('is_registered', true)
                            ->count()
                    ),

                // Total Registered
                TextColumn::make('total_registered')
                    ->label('Total Registered')
                    ->alignCenter()
                    ->weight('semibold')
                    ->getStateUsing(fn (Branch $record): int =>
                        Member::where('branch_number', $record->branch_number)
                            ->where('is_active', true)
                            ->where('is_registered', true)
                            ->count()
                    ),

                // Quorum %
                TextColumn::make('quorum_percentage')
                    ->label('Quorum %')
                    ->alignCenter()
                    ->badge()
                    ->getStateUsing(function (Branch $record): float {
                        $totalMigs = Member::where('branch_number', $record->branch_number)
                            ->where('is_active', true)
                            ->where('is_migs', true)
                            ->count();

                        $registeredMigs = Member::where('branch_number', $record->branch_number)
                            ->where('is_active', true)
                            ->where('is_migs', true)
                            ->where('is_registered', true)
                            ->count();

                        return $totalMigs > 0
                            ? round(($registeredMigs / $totalMigs) * 100, 2)
                            : 0;
                    })
                    ->formatStateUsing(fn (float $state): string => number_format($state, 2) . '%')
                    ->color(fn (float $state): string => match(true) {
                        $state >= 75 => 'success',
                        $state >= 50 => 'warning',
                        $state >= 25 => 'danger',
                        default      => 'gray',
                    }),

                // Votes Cast
                TextColumn::make('votes_cast')
                    ->label('Votes Cast')
                    ->alignCenter()
                    ->badge()
                    ->color('info')
                    ->getStateUsing(fn (Branch $record): int =>
                        Vote::where('branch_number', $record->branch_number)->count()
                    ),

            ])
            ->filters([

                // Filter by specific branch
                SelectFilter::make('branch_name')
                    ->label('Branch')
                    ->options(
                        Branch::where('is_active', true)
                            ->orderBy('branch_name')
                            ->pluck('branch_name', 'id')
                    )
                    ->searchable()
                    ->preload(),

                // Filter by quorum threshold
                SelectFilter::make('quorum_threshold')
                    ->label('Quorum Status')
                    ->options([
                        'above_75' => 'Above 75% (Excellent)',
                        'above_50' => 'Above 50% (Good)',
                        'above_25' => 'Above 25% (Fair)',
                        'below_25' => 'Below 25% (Poor)',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (! isset($data['value'])) {
                            return $query;
                        }

                        return $query->havingRaw(match($data['value']) {
                            'above_75' => 'quorum_percentage >= 75',
                            'above_50' => 'quorum_percentage >= 50 AND quorum_percentage < 75',
                            'above_25' => 'quorum_percentage >= 25 AND quorum_percentage < 50',
                            'below_25' => 'quorum_percentage < 25',
                            default    => '1=1',
                        });
                    }),

                // Filter branches with/without votes
                TernaryFilter::make('has_votes')
                    ->label('Has Votes Cast')
                    ->placeholder('All branches')
                    ->trueLabel('With votes')
                    ->falseLabel('Without votes')
                    ->queries(
                        true: fn (Builder $query) => $query->havingRaw('votes_cast > 0'),
                        false: fn (Builder $query) => $query->havingRaw('votes_cast = 0'),
                    ),

            ])
            ->persistFiltersInSession()
            ->paginated([10, 25, 50, 'all'])
            ->striped()
            ->defaultSort('branch_name')
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('No branches found')
            ->emptyStateDescription('No branches match your current filters.')
            ->emptyStateIcon('heroicon-o-building-office-2');
    }

    protected function getViewData(): array
    {
        return [
            'overallSummary' => $this->getOverallSummaryProperty(),
        ];
    }
}
