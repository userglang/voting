<?php

namespace App\Filament\Resources\Members\Pages;

use App\Filament\Resources\Members\MemberResource;
use App\Imports\MembersImport;
use App\Exports\MembersTemplateExport;
use App\Exports\MembersExport;
use App\Exports\MembersSummaryExport;
use App\Models\Branch;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\Width;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Storage;

class ListMembers extends ListRecords
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderActions(): array
    {
        return [

            // ── New Member ──────────────────────────────────────────────
            CreateAction::make()
                ->label('New Member')
                ->icon('heroicon-o-user-plus')
                ->tooltip('Register a new cooperative member'),

            // ── Export Reports ───────────────────────────────────────────
            ActionGroup::make([

                // Export Detailed Excel
                Action::make('exportExcel')
                    ->label('Detailed Report (Excel)')
                    ->icon('heroicon-o-table-cells')
                    ->color('success')
                    ->modalHeading('Export Detailed Members Report')
                    ->modalDescription('Apply filters below to narrow down the data before exporting.')
                    ->modalSubmitActionLabel('Export Now')
                    ->modalWidth('lg')
                    ->form([
                        Section::make('Filter Options')
                            ->description('Leave filters blank to export all records.')
                            ->icon('heroicon-o-funnel')
                            ->schema([
                                Select::make('branch_number')
                                    ->label('Branch')
                                    ->options(\App\Models\Branch::pluck('branch_name', 'branch_number'))
                                    ->searchable()
                                    ->placeholder('All Branches'),

                                Select::make('gender')
                                    ->label('Gender')
                                    ->options([
                                        'Male'   => 'Male',
                                        'Female' => 'Female',
                                    ])
                                    ->placeholder('All Genders'),

                                Radio::make('is_active')
                                    ->label('Member Status')
                                    ->options([
                                        'all'      => 'All',
                                        'active'   => 'Active Only',
                                        'inactive' => 'Inactive Only',
                                    ])
                                    ->default('all')
                                    ->inline()
                                    ->inlineLabel(false),

                                Radio::make('is_migs')
                                    ->label('MIGS Membership')
                                    ->options([
                                        'all' => 'All',
                                        'yes' => 'MIGS Only',
                                        'no'  => 'Non-MIGS Only',
                                    ])
                                    ->default('all')
                                    ->inline()
                                    ->inlineLabel(false),

                                Radio::make('is_registered')
                                    ->label('Registration Status')
                                    ->options([
                                        'all'            => 'All',
                                        'registered'     => 'Registered Only',
                                        'not_registered' => 'Not Registered Only',
                                    ])
                                    ->default('all')
                                    ->inline()
                                    ->inlineLabel(false),
                            ])
                            ->columns(2),
                    ])
                    ->action(function (array $data) {
                        try {
                            $filename = 'members-detailed-' . now()->format('Y-m-d-His') . '.xlsx';
                            return Excel::download(new MembersExport($data), $filename);
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Export Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // Export Summary Excel
                Action::make('exportSummary')
                    ->label('Summary Report (Excel)')
                    ->icon('heroicon-o-chart-bar')
                    ->color('info')
                    ->modalHeading('Export Summary Report')
                    ->modalDescription('Choose a branch to generate a summary, or leave blank for all branches.')
                    ->modalSubmitActionLabel('Export Summary')
                    ->modalWidth('md')
                    ->form([
                        Section::make('Filter Options')
                            ->icon('heroicon-o-funnel')
                            ->schema([
                                Select::make('branch_number')
                                    ->label('Branch')
                                    ->options(\App\Models\Branch::pluck('branch_name', 'branch_number'))
                                    ->searchable()
                                    ->placeholder('All Branches'),
                            ]),
                    ])
                    ->action(function (array $data) {
                        try {
                            $filename = 'members-summary-' . now()->format('Y-m-d-His') . '.xlsx';
                            return Excel::download(new MembersSummaryExport($data), $filename);
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Export Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // Export Summary PDF
                Action::make('exportPdfSummary')
                    ->label('Summary Report (PDF)')
                    ->icon('heroicon-o-document-text')
                    ->color('warning')
                    ->modalHeading('Export PDF Summary Report')
                    ->modalDescription('Choose a branch to generate a PDF summary, or leave blank for all branches.')
                    ->modalSubmitActionLabel('Generate PDF')
                    ->modalWidth('md')
                    ->form([
                        Section::make('Filter Options')
                            ->icon('heroicon-o-funnel')
                            ->schema([
                                Select::make('branch_number')
                                    ->label('Branch')
                                    ->options(\App\Models\Branch::pluck('branch_name', 'branch_number'))
                                    ->searchable()
                                    ->placeholder('All Branches'),
                            ]),
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

                                $totalMembers  = $memberQuery->count();
                                $totalMigs     = (clone $memberQuery)->where('is_migs', true)->count();
                                $totalNonMigs  = (clone $memberQuery)->where('is_migs', false)->count();

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
                                    'branch_number'      => $branch->branch_number,
                                    'branch_name'        => $branch->branch_name,
                                    'total_members'      => $totalMembers,
                                    'total_migs'         => $totalMigs,
                                    'total_non_migs'     => $totalNonMigs,
                                    'total_reg_migs'     => $totalRegMigs,
                                    'total_reg_non_migs' => $totalRegNonMigs,
                                    'quorum_percentage'  => $quorumPercentage,
                                    'total_casted_votes' => $totalCastedVotes,
                                ];
                            });

                            $totalMembers    = $summary->sum('total_members');
                            $totalBranches   = $summary->count();
                            $totalMigs       = $summary->sum('total_migs');
                            $totalNonMigs    = $summary->sum('total_non_migs');
                            $totalRegMigs    = $summary->sum('total_reg_migs');
                            $totalRegNonMigs = $summary->sum('total_reg_non_migs');
                            $totalRegistered = $totalRegMigs + $totalRegNonMigs;
                            $totalVotes      = $summary->sum('total_casted_votes');
                            $overallQuorum   = $totalMigs > 0 ? ($totalRegMigs / $totalMigs) * 100 : 0;

                            $pdf = Pdf::loadView('pdf.members-summary', [
                                'summary'         => $summary,
                                'totalMembers'    => $totalMembers,
                                'totalBranches'   => $totalBranches,
                                'totalMigs'       => $totalMigs,
                                'totalNonMigs'    => $totalNonMigs,
                                'totalRegMigs'    => $totalRegMigs,
                                'totalRegNonMigs' => $totalRegNonMigs,
                                'totalRegistered' => $totalRegistered,
                                'totalVotes'      => $totalVotes,
                                'overallQuorum'   => $overallQuorum,
                            ])->setPaper('a4', 'portrait');

                            $filename = 'members-summary-' . now()->format('Y-m-d-His') . '.pdf';

                            return response()->streamDownload(function () use ($pdf) {
                                echo $pdf->output();
                            }, $filename);

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Export Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

            ])
                ->label('Export Reports')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->button()
                ->tooltip('Export member reports in Excel or PDF format'),

            // ── Import Members ───────────────────────────────────────────
            Action::make('importMembers')
                ->label('Import Members')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info')
                ->modalWidth('2xl')
                ->modalHeading('Import Members from Excel / CSV')
                ->modalDescription('Upload a spreadsheet file to bulk-import members into a specific branch.')
                ->modalSubmitActionLabel('Start Import')
                ->modalIcon('heroicon-o-arrow-up-tray')
                ->form([
                    Section::make('Target Branch')
                        ->description('All imported members will be assigned to this branch.')
                        ->icon('heroicon-o-building-office-2')
                        ->schema([
                            Select::make('branch_number')
                                ->label('Branch')
                                ->options(
                                    \App\Models\Branch::where('is_active', true)
                                        ->orderBy('branch_name')
                                        ->pluck('branch_name', 'branch_number')
                                )
                                ->required()
                                ->searchable()
                                ->native(false)
                                ->placeholder('Select a branch'),
                        ]),

                    Section::make('Upload File')
                        ->description('Accepted formats: .xlsx, .xls, .csv — Maximum size: 10MB.')
                        ->icon('heroicon-o-paper-clip')
                        ->schema([
                            FileUpload::make('file')
                                ->label('Choose File')
                                ->acceptedFileTypes([
                                    'application/vnd.ms-excel',
                                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                    'text/csv',
                                ])
                                ->maxSize(10240)
                                ->required()
                                ->storeFiles(false)
                                ->visibility('private'),
                        ]),

                    Section::make('How to Format Your File')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Placeholder::make('instructions')
                                ->label('')
                                ->content(new HtmlString('
                                    <div class="space-y-3 text-sm">
                                        <div class="bg-green-50 border border-green-200 rounded-lg p-3">
                                            <p class="font-semibold text-green-800 mb-2">📋 Required Columns</p>
                                            <ul class="space-y-1 text-green-700 ml-1">
                                                <li>• <code class="bg-green-100 px-1 rounded">cid</code> — Member CID / ID</li>
                                                <li>• <code class="bg-green-100 px-1 rounded">first_name</code> + <code class="bg-green-100 px-1 rounded">last_name</code> &nbsp;<em>or</em>&nbsp; <code class="bg-green-100 px-1 rounded">full_name</code></li>
                                            </ul>
                                        </div>
                                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                            <p class="font-semibold text-blue-800 mb-2">📝 Optional Columns</p>
                                            <div class="grid grid-cols-2 gap-1 text-blue-700 ml-1">
                                                <span>• <code class="bg-blue-100 px-1 rounded">middle_name</code></span>
                                                <span>• <code class="bg-blue-100 px-1 rounded">birth_date</code> (YYYY-MM-DD)</span>
                                                <span>• <code class="bg-blue-100 px-1 rounded">gender</code> (Male / Female)</span>
                                                <span>• <code class="bg-blue-100 px-1 rounded">share_account</code></span>
                                                <span>• <code class="bg-blue-100 px-1 rounded">is_migs</code> (TRUE / FALSE)</span>
                                                <span>• <code class="bg-blue-100 px-1 rounded">share_amount</code></span>
                                            </div>
                                        </div>
                                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-3">
                                            <p class="font-semibold text-purple-800 mb-1">⚙️ Auto-Generated Member Code</p>
                                            <p class="text-purple-700">Format: <code class="bg-purple-100 px-1 rounded">OIC-{branch}-{cid}</code> &nbsp;e.g. <strong>OIC-011-123456</strong></p>
                                        </div>
                                    </div>
                                ')),
                        ])
                        ->collapsible()
                        ->collapsed(true),
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
                                ->title('No File Selected')
                                ->body('Please upload a file to continue.')
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
                                ->body('The uploaded file could not be located. Please try again.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $import = new MembersImport($data['branch_number']);
                        Excel::import($import, $path);

                        $imported = $import->getImportedCount();
                        $updated  = $import->getUpdatedCount();
                        $skipped  = $import->getSkippedCount();
                        $failures = $import->getFailures();

                        $branch     = \App\Models\Branch::where('branch_number', $data['branch_number'])->first();
                        $branchName = $branch ? $branch->branch_name : 'Unknown';

                        if (count($failures) > 0) {
                            $errorMessages = [];
                            foreach ($failures as $failure) {
                                $errorMessages[] = "Row {$failure->row()}: " . implode(', ', $failure->errors());
                            }

                            $errorSummary = array_slice($errorMessages, 0, 5);
                            $moreErrors   = count($errorMessages) > 5
                                ? "\n…and " . (count($errorMessages) - 5) . " more error(s)."
                                : '';

                            Notification::make()
                                ->title('Import Completed with Errors')
                                ->body("Branch: {$branchName}\n✅ Created: {$imported}  🔄 Updated: {$updated}  ⏭ Skipped: {$skipped}\n\n❌ Errors:\n" . implode("\n", $errorSummary) . $moreErrors)
                                ->warning()
                                ->duration(15000)
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Import Successful!')
                                ->body("Branch: {$branchName}\n✅ Created: {$imported}  🔄 Updated: {$updated}  ⏭ Skipped: {$skipped}")
                                ->success()
                                ->duration(10000)
                                ->send();
                        }

                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Import Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),

            // ── Download Template ────────────────────────────────────────
            Action::make('downloadTemplate')
                ->label('Download Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->tooltip('Download the Excel import template with sample data')
                ->action(function () {
                    return Excel::download(
                        new MembersTemplateExport(),
                        'members-import-template.xlsx'
                    );
                }),

            // ── Delete Members by Branch ─────────────────────────────────
            Action::make('deleteAllByBranch')
                ->label('Delete')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->modalWidth(Width::ExtraLarge)
                ->modalHeading('Delete All Members by Branch')
                ->modalDescription('Permanently remove all members from a selected branch. This action cannot be undone.')
                ->modalSubmitActionLabel('Delete All Members')
                ->modalIcon('heroicon-o-exclamation-triangle')
                ->modalIconColor('danger')
                ->tooltip('Permanently delete all members in a specific branch')
                ->form([
                    Section::make('Select Branch')
                        ->description('All members in the selected branch will be permanently deleted.')
                        ->icon('heroicon-o-building-office-2')
                        ->schema([
                            Select::make('branch_number')
                                ->label('Branch')
                                ->options(
                                    Branch::where('is_active', true)
                                        ->orderBy('branch_name')
                                        ->pluck('branch_name', 'branch_number')
                                )
                                ->required()
                                ->searchable()
                                ->native(false)
                                ->placeholder('Select a branch'),

                            Placeholder::make('warning_notice')
                                ->label('')
                                ->content(new HtmlString('
                                    <div class="flex gap-3 bg-red-50 border border-red-200 rounded-lg p-4 text-sm">
                                        <span class="text-red-500 text-lg mt-0.5">⚠️</span>
                                        <div>
                                            <p class="font-semibold text-red-800 mb-1">Irreversible Action</p>
                                            <p class="text-red-700">All members in the selected branch will be <strong>permanently deleted</strong> from the system. Make sure you have a backup before proceeding.</p>
                                        </div>
                                    </div>
                                ')),
                        ]),

                    Section::make('Identity Verification')
                        ->description('Enter your password to authorize this deletion.')
                        ->icon('heroicon-o-lock-closed')
                        ->schema([
                            TextInput::make('password')
                                ->label('Your Password')
                                ->password()
                                ->revealable()
                                ->required()
                                ->placeholder('Enter your current password')
                                ->helperText('Your password is required to confirm this irreversible action.'),
                        ]),
                ])
                ->action(function (array $data) {
                    try {
                        if (empty($data['branch_number'])) {
                            Notification::make()
                                ->title('Branch Required')
                                ->body('Please select a branch to proceed.')
                                ->danger()
                                ->send();
                            return;
                        }

                        if (!Hash::check($data['password'], Auth::user()->password)) {
                            Notification::make()
                                ->title('Incorrect Password')
                                ->body('The password you entered is wrong. Deletion has been cancelled.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $branch       = \App\Models\Branch::where('branch_number', $data['branch_number'])->first();
                        $branchName   = $branch ? $branch->branch_name : 'Unknown';
                        $deletedCount = \App\Models\Member::where('branch_number', $data['branch_number'])->count();

                        \App\Models\Member::where('branch_number', $data['branch_number'])->delete();

                        Notification::make()
                            ->title('Deletion Complete')
                            ->body("{$deletedCount} member(s) from {$branchName} have been permanently deleted.")
                            ->danger()
                            ->duration(8000)
                            ->send();

                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Deletion Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),

        ];
    }
}
