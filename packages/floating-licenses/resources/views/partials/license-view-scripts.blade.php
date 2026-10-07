{{-- [floating-licenses addon] Bug B: the info panel's core "Remaining" row
     (rendered by the shared x-info-panel component, which this addon must not
     edit) uses seat-based math and ignores floating allocations. Replace its
     value client-side with the floating availability (pool - active), keeping
     the same row and label; negative means over-allocated. Master-off /
     no-config renders exactly as core (this block is not output at all).
     Also: bulk-select checkboxes + client-side pagination for the floating
     assignments table (posts floating:<id> to the core bulk-checkin route).
     Expects $floatingConfig and $floatingStats. --}}
@if ($floatingConfig)
    @php
        $floatingRemaining = $floatingStats['pool_size'] - $floatingStats['active'];
        $floatingRemainingHtml = ($floatingRemaining < 0)
            ? '<span class="label label-warning">'.$floatingRemaining.'</span> '.trans('general.remaining')
            : $floatingRemaining.' '.trans('general.remaining');
    @endphp
    <script id="floating-remaining-override" data-remaining="{{ $floatingRemaining }}">
        document.addEventListener('DOMContentLoaded', function () {
            // The info-element row's id is str_slug(trans('general.remaining')).
            var remainingRow = document.getElementById('remaining');
            if (remainingRow) {
                remainingRow.innerHTML = @json($floatingRemainingHtml);
                @if ($floatingRemaining < 0)
                remainingRow.classList.remove('text-success');
                remainingRow.classList.add('text-danger');
                @endif
            }
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var PAGE_SIZE = 25;
            var rows = Array.prototype.slice.call(document.querySelectorAll('#floatingAssignedTable tbody tr'));
            var totalPages = Math.max(1, Math.ceil(rows.length / PAGE_SIZE));
            var currentPage = 1;

            var goButton = document.getElementById('floatingBulkCheckinButton');
            var countWrap = document.getElementById('floatingBulkCheckinCount');
            var selectAll = document.getElementById('floatingSelectAll');
            var pager = document.getElementById('floatingPager');
            var pagerInfo = document.getElementById('floatingPagerInfo');
            var pagerPages = document.getElementById('floatingPagerPages');

            function visibleRows() {
                var start = (currentPage - 1) * PAGE_SIZE;
                return rows.slice(start, start + PAGE_SIZE);
            }

            function renderPage() {
                rows.forEach(function (row) { row.style.display = 'none'; });
                visibleRows().forEach(function (row) { row.style.display = ''; });

                if (pager) { pager.style.display = rows.length > PAGE_SIZE ? '' : 'none'; }
                if (pagerInfo) {
                    var start = (currentPage - 1) * PAGE_SIZE + 1;
                    var end = Math.min(rows.length, currentPage * PAGE_SIZE);
                    pagerInfo.textContent = rows.length ? ('Showing ' + start + ' to ' + end + ' of ' + rows.length + ' rows') : '';
                }
                if (pagerPages) { pagerPages.textContent = currentPage + ' / ' + totalPages; }
                document.getElementById('floatingPagerPrev').disabled = currentPage === 1;
                document.getElementById('floatingPagerNext').disabled = currentPage === totalPages;

                refreshFloatingSelection();
            }

            function refreshFloatingSelection() {
                var checked = document.querySelectorAll('.floating-allocation-checkbox:checked').length;
                if (goButton) { goButton.disabled = checked === 0; }
                if (countWrap) {
                    countWrap.querySelector('.badge').textContent = checked;
                    countWrap.style.display = checked ? 'inline' : 'none';
                }
                if (selectAll) {
                    // select-all acts on the current page's rows
                    var pageBoxes = visibleRows().map(function (row) { return row.querySelector('.floating-allocation-checkbox'); })
                        .filter(function (box) { return box; });
                    var pageChecked = pageBoxes.filter(function (box) { return box.checked; }).length;
                    selectAll.indeterminate = pageChecked > 0 && pageChecked < pageBoxes.length;
                    selectAll.checked = pageBoxes.length > 0 && pageChecked === pageBoxes.length;
                }
            }

            document.addEventListener('change', function (event) {
                if (event.target.matches('.floating-allocation-checkbox')) { refreshFloatingSelection(); }
                if (event.target === selectAll) {
                    visibleRows().forEach(function (row) {
                        var box = row.querySelector('.floating-allocation-checkbox');
                        if (box) { box.checked = selectAll.checked; }
                    });
                    refreshFloatingSelection();
                }
            });

            document.getElementById('floatingPagerPrev').addEventListener('click', function () {
                if (currentPage > 1) { currentPage--; renderPage(); }
            });
            document.getElementById('floatingPagerNext').addEventListener('click', function () {
                if (currentPage < totalPages) { currentPage++; renderPage(); }
            });

            renderPage();
        });
    </script>
@endif
