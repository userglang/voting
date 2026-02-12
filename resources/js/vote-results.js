(function () {
    'use strict';

    /**
     * Read the results data from the hidden div's data attribute.
     * This avoids putting @json() inside <script> tags which Livewire
     * strips and renders as visible text on the page.
     */
    function getResults() {
        var el = document.getElementById('vote-results-data');
        if (!el) return [];
        try {
            return JSON.parse(el.getAttribute('data-results') || '[]');
        } catch (e) {
            console.error('VoteResults: could not parse results data', e);
            return [];
        }
    }

    /**
     * Print Result — just triggers the browser print dialog.
     */
    function doPrintResult() {
        window.print();
    }

    /**
     * Print Summary — builds a clean printable HTML page in a new tab.
     */
    function doPrintSummary() {
        var results = getResults();

        var html = '<!DOCTYPE html><html><head><title>Vote Results Summary</title>'
            + '<style>'
            + '* { margin:0; padding:0; box-sizing:border-box; }'
            + 'body { font-family: Arial, sans-serif; padding: 32px; color: #111; font-size: 13px; }'
            + 'h1   { font-size: 20px; font-weight: 700; margin-bottom: 4px; color: #1e3a8a; }'
            + '.sub { font-size: 11px; color: #6b7280; margin-bottom: 24px; }'
            + '.pos { margin-bottom: 24px; page-break-inside: avoid; }'
            + '.pos-header { display:flex; justify-content:space-between; align-items:center;'
            + '  background:#1d4ed8; color:white; padding:8px 14px; border-radius:6px 6px 0 0; }'
            + '.pos-title { font-size:13px; font-weight:700; }'
            + '.pos-meta  { font-size:11px; opacity:.85; }'
            + 'table { width:100%; border-collapse:collapse; border:1px solid #e5e7eb; border-top:none; }'
            + 'thead tr { background:#f9fafb; }'
            + 'th { padding:7px 12px; font-size:11px; text-transform:uppercase; color:#6b7280;'
            + '  border-bottom:1px solid #e5e7eb; font-weight:600; letter-spacing:.05em; }'
            + 'td { padding:8px 12px; border-bottom:1px solid #f3f4f6; }'
            + '.tc { text-align:center; }'
            + '.winning { color:#15803d; font-weight:700; }'
            + '.trailing { color:#6b7280; }'
            + 'tfoot td { background:#f9fafb; font-weight:700; border-top:2px solid #e5e7eb; }'
            + '</style></head><body>'
            + '<h1>Election Vote Results</h1>'
            + '<p class="sub">Generated: ' + new Date().toLocaleString() + '</p>';

        results.forEach(function (p) {
            var totalOnline = p.candidates.reduce(function (s, c) { return s + c.online_votes; }, 0);
            var totalOnsite = p.candidates.reduce(function (s, c) { return s + c.onsite_votes; }, 0);

            html += '<div class="pos">'
                + '<div class="pos-header">'
                + '<span class="pos-title">' + p.title + '</span>'
                + '<span class="pos-meta">' + p.slots + ' slot(s) &nbsp;&middot;&nbsp; ' + p.total_votes + ' total votes</span>'
                + '</div>'
                + '<table>'
                + '<thead><tr>'
                + '<th class="tc" style="width:36px">#</th>'
                + '<th>Candidate</th>'
                + '<th class="tc">Total</th>'
                + '<th class="tc">Online</th>'
                + '<th class="tc">On-site</th>'
                + '<th class="tc">Status</th>'
                + '</tr></thead><tbody>';

            p.candidates.forEach(function (c, i) {
                var winning = i < p.slots && c.total_votes > 0;
                var status  = c.total_votes === 0
                    ? '&mdash;'
                    : winning
                        ? '<span class="winning">&#10003; Winning</span>'
                        : '<span class="trailing">Trailing</span>';

                html += '<tr>'
                    + '<td class="tc">' + (i + 1) + '</td>'
                    + '<td>' + c.full_name + '</td>'
                    + '<td class="tc"><strong>' + c.total_votes + '</strong></td>'
                    + '<td class="tc">' + c.online_votes + '</td>'
                    + '<td class="tc">' + c.onsite_votes + '</td>'
                    + '<td class="tc">' + status + '</td>'
                    + '</tr>';
            });

            html += '</tbody>'
                + '<tfoot><tr>'
                + '<td colspan="2" style="text-align:right;padding-right:12px">Totals</td>'
                + '<td class="tc">' + p.total_votes + '</td>'
                + '<td class="tc">' + totalOnline + '</td>'
                + '<td class="tc">' + totalOnsite + '</td>'
                + '<td></td>'
                + '</tr></tfoot>'
                + '</table></div>';
        });

        html += '</body></html>';

        var win = window.open('', '_blank');
        win.document.write(html);
        win.document.close();
        setTimeout(function () { win.print(); }, 500);
    }

    /**
     * Wire up the print buttons directly by ID.
     * Plain onclick — no Livewire, no server round-trip, always fires.
     */
    function bindButtons() {
        var btnResult  = document.getElementById('btn-print-result');
        var btnSummary = document.getElementById('btn-print-summary');

        if (btnResult)  btnResult.addEventListener('click',  doPrintResult);
        if (btnSummary) btnSummary.addEventListener('click', doPrintSummary);
    }

    // Bind on first load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindButtons);
    } else {
        bindButtons();
    }

    // Re-bind after every Livewire page update (search/filter re-renders the DOM)
    document.addEventListener('livewire:navigated', bindButtons);
    document.addEventListener('livewire:morph',     bindButtons);

})();
