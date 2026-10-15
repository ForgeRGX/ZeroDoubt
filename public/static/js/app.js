// Confirmación antes de enviar formularios con data-confirm (p. ej. eliminar)
document.addEventListener('submit', function (e) {
    var msg = e.target.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) {
        e.preventDefault();
    }
});

// Etiquetas de columna para las tablas que se apilan en móvil (<768px)
document.querySelectorAll('table.table-stack').forEach(function (table) {
    var ths = table.querySelectorAll('thead th');
    var heads = Array.prototype.map.call(ths, function (th) { return th.textContent.trim(); });
    var kinds = Array.prototype.map.call(ths, function (th) { return th.getAttribute('data-col') || ''; });
    table.querySelectorAll('tbody tr, tfoot tr').forEach(function (tr) {
        var col = 0;
        Array.prototype.forEach.call(tr.children, function (td) {
            var span = td.colSpan || 1;
            if (span === 1 && heads[col]) { td.setAttribute('data-label', heads[col]); }
            if (span === 1 && kinds[col]) { td.setAttribute('data-col', kinds[col]); }
            col += span;
        });
    });
});

// Formularios de filtro con data-autosubmit: se aplican al cambiar un campo
document.querySelectorAll('form[data-autosubmit]').forEach(function (form) {
    var timer = null;
    form.addEventListener('change', function (e) {
        var t = e.target;
        if (!t || !t.name) { return; }
        // Un campo de fecha a medio escribir no debe lanzar la consulta
        if ((t.type === 'date' || t.type === 'month') && !t.value) { return; }
        clearTimeout(timer);
        timer = setTimeout(function () { form.submit(); }, 150);
    });
});

// Importe de ingresos: neto / bruto + retención, con cálculo en vivo
(function () {
    var box = document.getElementById('amount-fields');
    if (!box || box.getAttribute('data-income') !== '1') { return; }

    var es = document.documentElement.lang !== 'en';
    var locale = es ? 'es-ES' : 'en-GB';
    var money = new Intl.NumberFormat(locale, { style: 'currency', currency: 'EUR' });
    var pctFmt = new Intl.NumberFormat(locale, { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    var gross = document.getElementById('gross_amount');
    var pctInput = document.getElementById('withholding_pct');
    var net = document.getElementById('amount');
    var preview = document.getElementById('income-preview');
    var hint = document.getElementById('wh-default-hint');
    var category = document.getElementById('category_id');
    var netLabel = box.querySelector('label[for="amount"]');
    var fields = {};
    box.querySelectorAll('[data-field]').forEach(function (f) { fields[f.getAttribute('data-field')] = f; });
    var defaults = {};
    try { defaults = JSON.parse(document.getElementById('category-withholdings').textContent) || {}; } catch (e) { defaults = {}; }

    function parse(v) {
        var s = String(v || '').replace(/[\s €%]/g, '');
        if (s === '') { return NaN; }
        var hasComma = s.indexOf(',') !== -1, hasDot = s.indexOf('.') !== -1;
        if (hasComma && hasDot) {
            s = s.lastIndexOf(',') > s.lastIndexOf('.') ? s.replace(/\./g, '').replace(',', '.') : s.replace(/,/g, '');
        } else if (hasComma) {
            s = s.replace(',', '.');
        }
        return /^\d+(\.\d+)?$/.test(s) ? parseFloat(s) : NaN;
    }
    function plain(n) { var s = (Math.round(n * 100) / 100).toFixed(2); return es ? s.replace('.', ',') : s; }
    function mode() {
        var r = document.querySelector('input[name="income_mode"]:checked');
        return r ? r.value : 'net';
    }
    function show(name, visible) {
        var f = fields[name];
        if (!f) { return; }
        f.hidden = !visible;
        var input = f.querySelector('input');
        if (input) { input.disabled = !visible; }
    }
    function fill(tpl, args) {
        var i = 0;
        return tpl.replace(/%s/g, function () { return args[i++]; });
    }

    function update() {
        var m = mode();
        show('gross_amount', m !== 'net');
        show('withholding_pct', m === 'gross_pct');
        net.readOnly = (m === 'gross_pct');
        if (netLabel) { netLabel.textContent = netLabel.getAttribute('data-label-net'); }

        var g = parse(gross.value), p = parse(pctInput.value), n = parse(net.value);
        var text = '';
        if (m === 'gross_pct') {
            if (!isNaN(g) && !isNaN(p) && p < 100) {
                var computed = Math.round(g * (1 - p / 100) * 100) / 100;
                net.value = plain(computed);
                text = fill(preview.getAttribute('data-tpl-split'), [money.format(g - computed), money.format(computed)]);
            } else {
                net.value = '';
            }
        } else if (m === 'gross_net') {
            if (!isNaN(g) && !isNaN(n) && g > 0 && n <= g) {
                text = fill(preview.getAttribute('data-tpl-pct'), [money.format(g - n), pctFmt.format((g - n) / g * 100) + ' %']);
            }
        }
        preview.hidden = text === '';
        preview.textContent = text;
    }

    function proposeDefault() {
        if (mode() !== 'gross_pct' || !category) { return; }
        var v = defaults[category.value];
        if (v === undefined || v === null || v === '') { hint.hidden = true; return; }
        var shown = es ? String(v).replace('.', ',') : String(v);
        hint.textContent = fill(hint.getAttribute('data-template'), [shown + ' %']);
        hint.hidden = false;
        if (pctInput.value.trim() === '') { pctInput.value = shown; update(); }
    }

    document.querySelectorAll('input[name="income_mode"]').forEach(function (r) {
        r.addEventListener('change', function () { update(); proposeDefault(); });
    });
    [gross, pctInput, net].forEach(function (i) { if (i) { i.addEventListener('input', update); } });
    if (category) { category.addEventListener('change', proposeDefault); }
    update();
    proposeDefault();
})();

// Formulario de categoría: color, icono y vista previa
(function () {
    var form = document.getElementById('category-form');
    if (!form) { return; }
    var color = form.querySelector('#color');
    var name = form.querySelector('#name');
    var preview = document.getElementById('category-preview');
    var swatches = form.querySelectorAll('.swatch');
    var fallback = preview.textContent.trim();

    function selectedIcon() {
        var r = form.querySelector('input[name="icon"]:checked');
        return r ? r.value : 'tag';
    }
    function render() {
        var b = document.createElement('span');
        b.className = 'badge badge-cat';
        b.style.setProperty('--tc', color.value);
        var i = document.createElement('i');
        i.className = 'bi bi-' + selectedIcon();
        b.appendChild(i);
        b.appendChild(document.createTextNode(name.value.trim() || fallback));
        preview.replaceChildren(b);
        swatches.forEach(function (s) { s.classList.toggle('active', s.getAttribute('data-color').toLowerCase() === color.value.toLowerCase()); });
    }
    swatches.forEach(function (s) {
        s.addEventListener('click', function () { color.value = s.getAttribute('data-color'); render(); });
    });
    color.addEventListener('input', render);
    name.addEventListener('input', render);
    form.querySelectorAll('input[name="icon"]').forEach(function (r) { r.addEventListener('change', render); });
    render();
})();
