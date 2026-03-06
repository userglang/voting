<?php

namespace App\Exports;

use App\Models\Vote;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldQueueWithoutChain;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class VotesExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithStyles,
    WithColumnWidths,
    WithTitle,
    WithChunkReading,
    ShouldQueueWithoutChain
{
    public function __construct(protected array $filters = []) {}

    // -------------------------------------------------------------------------
    // Query
    // -------------------------------------------------------------------------

    public function query()
    {
        $query = Vote::query()
            ->select('votes.*')
            ->distinct()
            ->with([
                'member:id,code,last_name,first_name,middle_name',
                'candidate:id,position_id,first_name,last_name',
                'candidate.position:id,title',
                'branch:id,branch_number,branch_name',
            ])
            ->orderBy('created_at', 'desc')
            ->orderBy('id'); // stable ordering for chunking

        $this->applyFilters($query);

        return $query;
    }

    private function applyFilters(\Illuminate\Database\Eloquent\Builder $query): void
    {
        if (!empty($this->filters['branch_number'])) {
            $query->where('branch_number', $this->filters['branch_number']);
        }

        match ($this->filters['vote_type'] ?? 'all') {
            'online'  => $query->where('online_vote', true),
            'offline' => $query->where('online_vote', false),
            default   => null,
        };

        $isValid = $this->filters['is_valid'] ?? 'all';

        if (in_array($isValid, [true, 1, '1', 'true'], true)) {
            $isValid = 'valid';
        }

        if (in_array($isValid, [false, 0, '0', 'false'], true)) {
            $isValid = 'invalid';
        }

        match ($isValid) {
            'valid'   => $query->where('is_valid', true),
            'invalid' => $query->where('is_valid', false),
            default   => null,
        };

        if (!empty($this->filters['position_id'])) {
            $query->whereHas('candidate', fn ($q) =>
                $q->where('position_id', $this->filters['position_id'])
            );
        }

        if (!empty($this->filters['date_from'])) {
            $timeFrom = $this->filters['time_from'] ?? '00:00';
            $query->where('created_at', '>=', "{$this->filters['date_from']} {$timeFrom}:00");
        }

        if (!empty($this->filters['date_to'])) {
            $timeTo = $this->filters['time_to'] ?? '23:59';
            $query->where('created_at', '<=', "{$this->filters['date_to']} {$timeTo}:59");
        }
    }

    // -------------------------------------------------------------------------
    // Headings
    // -------------------------------------------------------------------------

    public function headings(): array
    {
        return [
            'ID',
            'Control Number',
            'Branch Number',
            'Branch Name',
            'Branch ID',
            'Member Code',
            'Member Complete Name',
            'Last Name',
            'First Name',
            'Middle Name',
            'Candidate ID',
            'Candidate Name',
            'Position',
            'Vote Type',
            'Valid',
            'Date Cast',
            'Time Cast',
        ];
    }

    // -------------------------------------------------------------------------
    // Row mapping
    // -------------------------------------------------------------------------

    public function map($vote): array
    {
        $candidate = $vote->candidate;

        $candidateName = ($candidate !== null)
            ? trim("{$candidate->first_name} {$candidate->last_name}")
            : 'N/A';

        return [
            $vote->id,
            $vote->control_number ?? 'N/A',
            $vote->branch_number ?? 'N/A',
            $vote->branch?->branch_name ?? 'N/A',
            $vote->branch?->id ?? 'N/A',
            $vote->member_code ?? 'N/A',
            $vote->member?->full_name ?? 'N/A',
            $vote->member?->last_name ?? 'N/A',
            $vote->member?->first_name ?? 'N/A',
            $vote->member?->middle_name ?? 'N/A',
            $vote->candidate_id ?? 'N/A',
            $candidateName,
            $candidate?->position?->title ?? 'N/A',
            $vote->online_vote ? 'Online' : 'Offline',
            $vote->is_valid ? 'Yes' : 'No',
            $vote->created_at?->format('Y-m-d') ?? 'N/A',
            $vote->created_at?->format('H:i:s') ?? 'N/A',
        ];
    }

    // -------------------------------------------------------------------------
    // Styling
    // -------------------------------------------------------------------------

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => [
                    'bold'  => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size'  => 12,
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 38,
            'B' => 18,
            'C' => 18,
            'D' => 30,
            'E' => 38,
            'F' => 18,
            'G' => 35,
            'H' => 20,
            'I' => 20,
            'J' => 20,
            'K' => 38,
            'L' => 35,
            'M' => 25,
            'N' => 12,
            'O' => 10,
            'P' => 15,
            'Q' => 12,
        ];
    }

    // -------------------------------------------------------------------------
    // Metadata
    // -------------------------------------------------------------------------

    public function title(): string
    {
        return 'Votes Report';
    }

    // -------------------------------------------------------------------------
    // Chunk Reading
    // -------------------------------------------------------------------------

    public function chunkSize(): int
    {
        return 1000;
    }
}
