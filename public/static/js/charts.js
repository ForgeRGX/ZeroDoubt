(function () {
    var el = document.getElementById('chart-data');
    if (!el || !window.Chart) { return; }
    var d = JSON.parse(el.textContent);
    var L = d.i18n || {};
    var locale = document.documentElement.lang === 'en' ? 'en-GB' : 'es-ES';
    var eur = new Intl.NumberFormat(locale, { style: 'currency', currency: 'EUR' });
    var eur0 = new Intl.NumberFormat(locale, { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 });
    var pct = new Intl.NumberFormat(locale, { maximumFractionDigits: 1 });
    var $ = function (id) { return document.getElementById(id); };

    var C = { income: '#2f9e6b', withholding: '#a9dcc2', expense: '#d1584b', balance: '#0e7c7b', gold: '#d9a441', grid: 'rgba(29,36,51,.07)' };

    Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
    Chart.defaults.color = '#4b5563';
    Chart.defaults.borderColor = C.grid;
    Chart.defaults.plugins.legend.labels.usePointStyle = true;

    var moneyAxis = { beginAtZero: true, ticks: { callback: function (v) { return eur0.format(v); } } };
    var tipMoney = { callbacks: { label: function (c) {
        var v = c.parsed.y !== undefined && c.parsed.y !== null ? c.parsed.y : c.parsed.x;
        return ' ' + c.dataset.label + ': ' + eur.format(v);
    } } };

    function doughnut(canvas, data) {
        if (!canvas || !data || !data.values.length) { return; }
        new Chart(canvas, {
            type: 'doughnut',
            data: { labels: data.labels, datasets: [{ data: data.values, backgroundColor: data.colors, borderColor: 'rgba(255,255,255,.9)', borderWidth: 3 }] },
            options: {
                responsive: true, maintainAspectRatio: false, cutout: '58%',
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: { callbacks: { label: function (c) {
                        var total = c.dataset.data.reduce(function (a, b) { return a + b; }, 0);
                        return ' ' + c.label + ': ' + eur.format(c.parsed) + ' (' + pct.format(c.parsed / total * 100) + ' %)';
                    } } }
                }
            }
        });
    }

    // Ingresos (neto + retención = bruto, apilados) frente a gastos, con la línea del balance
    function incomeVsExpense(canvas, s, withBalance) {
        if (!canvas || !s.labels.length) { return; }
        var hasWh = s.withholding.some(function (v) { return v > 0; });
        var datasets = [
            { type: 'bar', label: L.income, data: s.income_net, backgroundColor: C.income, borderRadius: 4, stack: 'in', order: 2 }
        ];
        if (hasWh) {
            datasets.push({ type: 'bar', label: L.withholding, data: s.withholding, backgroundColor: C.withholding, borderRadius: 4, stack: 'in', order: 2 });
        }
        datasets.push({ type: 'bar', label: L.expense, data: s.expense, backgroundColor: C.expense, borderRadius: 4, stack: 'out', order: 2 });
        if (withBalance) {
            datasets.push({ type: 'line', label: L.balance, data: s.balance, borderColor: C.balance, backgroundColor: C.balance,
                tension: .25, pointRadius: 3, borderWidth: 2, order: 1 });
        }
        new Chart(canvas, {
            data: { labels: s.labels, datasets: datasets },
            options: {
                responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                scales: { x: { stacked: true, grid: { display: false } }, y: Object.assign({ stacked: true }, moneyAxis) },
                plugins: { legend: { position: 'bottom' }, tooltip: tipMoney }
            }
        });
    }

    function cumulative(canvas, s) {
        if (!canvas || !s.labels.length) { return; }
        new Chart(canvas, {
            type: 'line',
            data: { labels: s.labels, datasets: [{ label: L.cumulative, data: s.cumulative, borderColor: C.balance,
                backgroundColor: 'rgba(14,124,123,.14)', fill: true, tension: .25, pointRadius: s.labels.length > 40 ? 0 : 3 }] },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: { x: { grid: { display: false } }, y: { ticks: { callback: function (v) { return eur0.format(v); } } } },
                plugins: { legend: { display: false }, tooltip: tipMoney }
            }
        });
    }

    function balanceBars(canvas, s) {
        if (!canvas || !s.labels.length) { return; }
        new Chart(canvas, {
            type: 'bar',
            data: { labels: s.labels, datasets: [{ label: L.balance, data: s.balance, borderRadius: 4,
                backgroundColor: s.balance.map(function (v) { return v < 0 ? C.expense : C.income; }) }] },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: { x: { grid: { display: false } }, y: { ticks: { callback: function (v) { return eur0.format(v); } } } },
                plugins: { legend: { display: false }, tooltip: tipMoney }
            }
        });
    }

    function yearOverYear(canvas, s) {
        if (!canvas || !s.prev_expense) { return; }
        new Chart(canvas, {
            type: 'line',
            data: { labels: s.labels, datasets: [
                { label: L.this_year, data: s.expense, borderColor: C.expense, backgroundColor: C.expense, tension: .25, pointRadius: 3 },
                { label: L.prev_year, data: s.prev_expense, borderColor: '#94a3b8', backgroundColor: '#94a3b8', borderDash: [6, 4], tension: .25, pointRadius: 3 }
            ] },
            options: {
                responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                scales: { x: { grid: { display: false } }, y: moneyAxis },
                plugins: { legend: { position: 'bottom' }, tooltip: tipMoney }
            }
        });
    }

    function budgets(canvas, b) {
        if (!canvas || !b || !b.labels.length) { return; }
        new Chart(canvas, {
            type: 'bar',
            data: { labels: b.labels, datasets: [
                { label: L.budget, data: b.budget, backgroundColor: 'rgba(100,116,139,.35)', borderRadius: 4 },
                { label: L.spent, data: b.spent, borderRadius: 4,
                  backgroundColor: b.spent.map(function (v, i) { return v > b.budget[i] ? C.expense : C.income; }) }
            ] },
            options: {
                indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                scales: { x: moneyAxis, y: { grid: { display: false } } },
                plugins: { legend: { position: 'bottom' }, tooltip: tipMoney }
            }
        });
    }

    var s = d.series;
    if (s) {
        incomeVsExpense($('chart-trend'), s, true);
        incomeVsExpense($('chart-bars'), s, true);
        cumulative($('chart-cumulative'), s);
        balanceBars($('chart-balance'), s);
        yearOverYear($('chart-yoy'), s);
    }
    budgets($('chart-budgets'), d.budgets);
    doughnut($('chart-expense-cats'), d.expenseCats);
    doughnut($('chart-income-cats'), d.incomeCats);
})();
