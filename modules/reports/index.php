<?php
require_once __DIR__ . '/../../app/Core/bootstrap.php';
require_role(['admin', 'pharmacist']);

$pageTitle = 'Reports';
$activeModule = 'reports';
require_once __DIR__ . '/../../templates/header.php';
require_once __DIR__ . '/../../templates/sidebar.php';
?>
<main class="main-panel">
    <?php require_once __DIR__ . '/../../templates/topbar.php'; ?>
    <section class="content-area">
        <section class="panel">
            <div class="panel-header">
                <h2>Reports</h2>
            </div>

            <form id="report-filters" class="mb-3">
                <label>From: <input type="date" id="fromDate" name="from"></label>
                <label>To: <input type="date" id="toDate" name="to"></label>
                <button type="button" id="runReport" class="btn">Run</button>
            </form>

            <div id="reports">
                <section class="panel" id="summary-panel">
                    <h3>Summary</h3>
                    <ul id="summary-list">
                        <li>Total Sales: <strong id="total-sales">-</strong></li>
                        <li>Total Purchases: <strong id="total-purchases">-</strong></li>
                        <li>Cost of Goods Sold: <strong id="cogs">-</strong></li>
                        <li>Total Expenses: <strong id="total-expenses">-</strong></li>
                        <li>Gross Profit: <strong id="gross-profit">-</strong></li>
                    </ul>
                </section>

                <section class="panel" id="top-products-panel">
                    <h3>Top Products (by quantity sold)</h3>
                    <table id="top-products" class="table">
                        <thead>
                            <tr>
                                <th>Medicine</th>
                                <th>Quantity</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </section>

                <section class="panel" id="low-stock-panel">
                    <h3>Low / Minimum Stock</h3>
                    <table id="low-stock" class="table">
                        <thead>
                            <tr>
                                <th>Medicine</th>
                                <th>Qty</th>
                                <th>Min Stock</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </section>
            </div>
        </section>
    </section>

    <script>
        (function() {
            const $ = id => document.getElementById(id);

            function formatCurrency(v) {
                return Number(v).toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            async function fetchReport() {
                const from = $('fromDate').value || '';
                const to = $('toDate').value || '';
                const url = new URL('data.php', window.location.href);
                if (from) url.searchParams.set('from', from);
                if (to) url.searchParams.set('to', to);
                url.searchParams.set('type', 'summary');

                const res = await fetch(url.toString());
                if (!res.ok) return console.error('Failed to fetch report');
                const data = await res.json();

                $('total-sales').textContent = formatCurrency(data.summary.total_sales || 0);
                $('total-purchases').textContent = formatCurrency(data.summary.total_purchases || 0);
                $('cogs').textContent = formatCurrency(data.summary.cogs || 0);
                $('total-expenses').textContent = formatCurrency(data.summary.total_expenses || 0);
                $('gross-profit').textContent = formatCurrency(data.summary.gross_profit || 0);

                // top products
                const tpBody = document.querySelector('#top-products tbody');
                tpBody.innerHTML = '';
                (data.top_products || []).forEach(row => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `<td>${escapeHtml(row.medicine_name)}</td><td>${row.qty}</td><td>${formatCurrency(row.revenue)}</td>`;
                    tpBody.appendChild(tr);
                });

                // low stock
                const lsBody = document.querySelector('#low-stock tbody');
                lsBody.innerHTML = '';
                (data.low_stock || []).forEach(row => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `<td>${escapeHtml(row.medicine_name)}</td><td>${row.quantity}</td><td>${row.minimum_stock}</td>`;
                    lsBody.appendChild(tr);
                });
            }

            function escapeHtml(s) {
                return String(s).replace(/[&<>\\\"]/g, function(c) {
                    return {
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;'
                    } [c];
                });
            }

            $('runReport').addEventListener('click', fetchReport);
            // run once on load with defaults (last 30 days)
            const to = new Date();
            const from = new Date();
            from.setDate(to.getDate() - 30);
            $('fromDate').value = from.toISOString().slice(0, 10);
            $('toDate').value = to.toISOString().slice(0, 10);
            fetchReport();
        })();
    </script>
</main>
<?php require_once __DIR__ . '/../../templates/footer.php'; ?>