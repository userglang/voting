<?php

namespace App\Filament\Resources\Members\Pages;

use App\Filament\Resources\Members\MemberResource;
use App\Imports\MembersImport;
use App\Exports\MembersTemplateExport;
use App\Exports\MembersExport;
use App\Exports\MembersSummaryExport;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Radio;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Schemas\Components\Section;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Storage;

class ListMembers extends ListRecords
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            // Export Reports Action Group
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

            // Import Members Action
            Action::make('importMembers')
                ->label('Import Members')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->modalWidth('2xl')
                ->modalHeading('Import Members from Excel/CSV')
                ->modalDescription('Upload an Excel or CSV file to import members to a specific branch.')
                ->form([
                    Section::make('Branch Selection')
                        ->description('Select the branch where members will be imported')
                        ->schema([
                            Select::make('branch_number')
                                ->label('Branch')
                                ->options(\App\Models\Branch::where('is_active', true)
                                    ->orderBy('branch_name')
                                    ->pluck('branch_name', 'branch_number'))
                                ->required()
                                ->searchable()
                                ->native(false)
                                ->helperText('All imported members will be assigned to this branch')
                                ->placeholder('Select a branch'),
                        ]),

                    Section::make('File Upload')
                        ->description('Select your Excel (.xlsx, .xls) or CSV file')
                        ->schema([
                            FileUpload::make('file')
                                ->label('Select File')
                                ->acceptedFileTypes([
                                    'application/vnd.ms-excel',
                                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                    'text/csv',
                                ])
                                ->maxSize(10240)
                                ->required()
                                ->helperText('Maximum file size: 10MB. Accepted formats: .xlsx, .xls, .csv')
                                ->storeFiles(false)
                                ->visibility('private'),
                        ]),

                    Section::make('Import Instructions')
                        ->schema([
                            Placeholder::make('instructions')
                                ->content(new HtmlString('
                                    <div class="space-y-3 text-sm">
                                        <div class="bg-green-50 border-l-4 border-green-500 p-3 rounded">
                                            <p class="font-semibold text-green-800 mb-2">📋 Required Fields:</p>
                                            <ul class="list-disc list-inside space-y-1 text-green-700 ml-2">
                                                <li><strong>cid</strong> - Member CID/ID (Required)</li>
                                                <li><strong>first_name</strong> - First name (or use full_name)</li>
                                                <li><strong>last_name</strong> - Last name (or use full_name)</li>
                                            </ul>
                                        </div>

                                        <div class="bg-blue-50 border-l-4 border-blue-500 p-3 rounded">
                                            <p class="font-semibold text-blue-800 mb-2">📝 Available Fields:</p>
                                            <ul class="list-disc list-inside space-y-1 text-blue-700 ml-2">
                                                <li><strong>full_name</strong> - Complete name (e.g., "Dela Cruz, Juan Santos")</li>
                                                <li><strong>middle_name</strong> - Middle name</li>
                                                <li><strong>birth_date</strong> - Date of birth (YYYY-MM-DD)</li>
                                                <li><strong>gender</strong> - Male or Female</li>
                                                <li><strong>share_account</strong> - Share account number</li>
                                                <li><strong>is_migs</strong> - MIGS member (TRUE/FALSE)</li>
                                                <li><strong>share_amount</strong> - Share amount (number)</li>
                                            </ul>
                                        </div>

                                        <div class="bg-purple-50 border-l-4 border-purple-500 p-3 rounded">
                                            <p class="font-semibold text-purple-800 mb-2">⚙️ Code Generation:</p>
                                            <ul class="list-disc list-inside space-y-1 text-purple-700 ml-2">
                                                <li>Member code auto-generated as: <strong>OIC-{branch}-{cid}</strong></li>
                                                <li>Example: OIC-011-123456</li>
                                            </ul>
                                        </div>
                                    </div>
                                ')),
                        ])
                        ->collapsible()
                        ->collapsed(false),
                ])
                ->action(function (array $data) {
                    try {
                        if (empty($data['branch_number'])) {
                            Notification::make()
                                ->title('Branch Required')
                                ->body('Please select a branch before importing.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $file = $data['file'];

                        if (!$file) {
                            Notification::make()
                                ->title('No File Uploaded')
                                ->body('Please select a file to import.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $path = $file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile
                            ? $file->getRealPath()
                            : Storage::path($file);

                        if (!file_exists($path)) {
                            Notification::make()
                                ->title('File Not Found')
                                ->body('The uploaded file could not be found. Please try again.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $import = new MembersImport($data['branch_number']);
                        Excel::import($import, $path);

                        $imported = $import->getImportedCount();
                        $updated = $import->getUpdatedCount();
                        $skipped = $import->getSkippedCount();
                        $failures = $import->getFailures();

                        $branch = \App\Models\Branch::where('branch_number', $data['branch_number'])->first();
                        $branchName = $branch ? $branch->branch_name : 'Unknown';

                        if (count($failures) > 0) {
                            $errorMessages = [];
                            foreach ($failures as $failure) {
                                $errorMessages[] = "Row {$failure->row()}: " . implode(', ', $failure->errors());
                            }

                            $errorSummary = count($errorMessages) > 5
                                ? array_slice($errorMessages, 0, 5)
                                : $errorMessages;

                            $moreErrors = count($errorMessages) > 5
                                ? "\n... and " . (count($errorMessages) - 5) . " more errors"
                                : "";

                            Notification::make()
                                ->title('Import Completed with Errors')
                                ->body("Branch: {$branchName}\n✅ Created: {$imported} | 🔄 Updated: {$updated} | ⏭️ Skipped: {$skipped}\n\n❌ Errors:\n" . implode("\n", $errorSummary) . $moreErrors)
                                ->warning()
                                ->duration(15000)
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Import Successful! 🎉')
                                ->body("Branch: {$branchName}\n✅ Created: {$imported} members\n🔄 Updated: {$updated} members\n⏭️ Skipped: {$skipped} rows")
                                ->success()
                                ->duration(10000)
                                ->send();
                        }

                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Import Failed')
                            ->body('Error: ' . $e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),

            // Download Template Action
            Action::make('downloadTemplate')
                ->label('Download Template')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->action(function () {
                    return Excel::download(
                        new MembersTemplateExport(),
                        'members-import-template.xlsx'
                    );
                })
                ->tooltip('Download Excel template with sample data'),
        ];
    }
}
