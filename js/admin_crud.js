const API = '../controller/crud_controller.php';

// Wrapper de fetch que intercepta respostas de sessão expirada.
function apiFetch(url, options) {
    return fetch(url, options)
        .then(r => r.json())
        .then(data => {
            if (data.expirado) {
                window.location.href = 'admin_login.php?timeout=1';
                throw new Error('expirado');
            }
            return data;
        });
}

// ── Estado da sessão ───────────────────────────────────────────────────────
let currentTable   = null;
let currentColumns = [];
let currentPkCol   = null;
let editingRow     = null;
let currentRows    = [];

// ── Inicialização ──────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    apiFetch(`${API}?action=list_tables`)
        .then(data => {
            const list = document.getElementById('tableList');
            if (!data.sucesso || !data.data.length) {
                list.innerHTML = '<li class="loading">Nenhuma tabela encontrada.</li>';
                return;
            }
            list.innerHTML = data.data
                .map(t => `<li onclick="selectTable('${t}')">${t}</li>`)
                .join('');
        })
        .catch(() => {
            document.getElementById('tableList').innerHTML =
                '<li class="loading">Erro ao carregar tabelas.</li>';
        });
});

// ── Gerenciamento de painéis ───────────────────────────────────────────────
// Mostra apenas um painel de cada vez, esconde os demais.
function showPanel(id) {
    ['placeholder', 'tableArea', 'broadcastArea', 'bannedIpsArea'].forEach(p => {
        document.getElementById(p).classList.add('hidden');
    });
    document.getElementById(id).classList.remove('hidden');
}

function clearSidebarActive() {
    document.querySelectorAll('#toolList li, #tableList li').forEach(li => {
        li.classList.remove('active');
    });
}

// ── Ferramentas ────────────────────────────────────────────────────────────

function openBroadcast() {
    clearSidebarActive();
    document.querySelector('#toolList li[data-tool="broadcast"]').classList.add('active');
    showPanel('broadcastArea');
    // Limpa resultado anterior
    document.getElementById('broadcastResult').classList.add('hidden');
    document.getElementById('broadcastMsg').className = 'mensagem hidden';
}

function openBannedIps() {
    clearSidebarActive();
    document.querySelector('#toolList li[data-tool="bannedips"]').classList.add('active');
    showPanel('bannedIpsArea');
    loadBannedIps();
}

// ── Broadcast ──────────────────────────────────────────────────────────────

function sendBroadcast(e) {
    e.preventDefault();
    const btn  = e.target.querySelector('button[type="submit"]');
    const text = document.getElementById('broadcastText').value.trim();
    if (!text) return;

    btn.disabled    = true;
    btn.textContent = 'Enviando...';

    const formData = new FormData();
    formData.append('action',  'broadcast_send');
    formData.append('message', text);

    apiFetch(API, { method: 'POST', body: formData })
        .then(data => {
            const result = document.getElementById('broadcastResult');
            result.textContent = data.mensagem;
            result.className   = 'broadcast-result ' + (data.sucesso ? 'sucesso' : 'erro');
            if (data.sucesso) {
                document.getElementById('broadcastText').value = '';
            }
        })
        .catch(() => {
            const result = document.getElementById('broadcastResult');
            result.textContent = 'Erro de comunicação com o servidor.';
            result.className   = 'broadcast-result erro';
        })
        .finally(() => {
            btn.disabled    = false;
            btn.textContent = '📢 Enviar para todos';
        });
}

// ── IPs Banidos ────────────────────────────────────────────────────────────

function loadBannedIps() {
    const tbody = document.getElementById('banTableBody');
    tbody.innerHTML = '<tr class="empty-row"><td colspan="5">Carregando...</td></tr>';

    apiFetch(`${API}?action=banip_list`)
        .then(data => {
            if (!data.sucesso) {
                tbody.innerHTML = `<tr class="empty-row"><td colspan="5">${escHtml(data.mensagem)}</td></tr>`;
                return;
            }
            if (!data.data.length) {
                tbody.innerHTML = '<tr class="empty-row"><td colspan="5">Nenhum IP banido.</td></tr>';
                return;
            }
            tbody.innerHTML = data.data.map(row => `
                <tr>
                    <td>${escHtml(row.ip_address)}</td>
                    <td>${escHtml(row.reason || '—')}</td>
                    <td>${escHtml(row.banned_at)}</td>
                    <td>${escHtml(row.banned_by || '—')}</td>
                    <td class="acoes">
                        <button class="btn-editar" onclick="unbanIp(${escHtml(row.id)})">Desbanir</button>
                    </td>
                </tr>`).join('');
        })
        .catch(() => {
            tbody.innerHTML = '<tr class="empty-row"><td colspan="5">Erro ao carregar IPs.</td></tr>';
        });
}

function banIp(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('action', 'banip_add');

    apiFetch(API, { method: 'POST', body: formData })
        .then(data => {
            mostrarMensagemBan(data.mensagem, data.sucesso);
            if (data.sucesso) {
                e.target.reset();
                loadBannedIps();
            }
        })
        .catch(() => mostrarMensagemBan('Erro de comunicação com o servidor.', false));
}

function unbanIp(id) {
    if (!confirm('Desbanir este IP?')) return;

    const formData = new FormData();
    formData.append('action', 'banip_remove');
    formData.append('id',     id);

    apiFetch(API, { method: 'POST', body: formData })
        .then(data => {
            mostrarMensagemBan(data.mensagem, data.sucesso);
            if (data.sucesso) loadBannedIps();
        })
        .catch(() => mostrarMensagemBan('Erro de comunicação com o servidor.', false));
}

function mostrarMensagemBan(texto, sucesso) {
    const el = document.getElementById('banMsg');
    el.textContent = texto;
    el.className   = 'mensagem ' + (sucesso ? 'sucesso' : 'erro');
    clearTimeout(el._timeout);
    el._timeout = setTimeout(() => { el.className = 'mensagem hidden'; }, 4000);
}

// ── Selecionar tabela ──────────────────────────────────────────────────────
function selectTable(table) {
    currentTable = table;
    editingRow   = null;

    clearSidebarActive();
    document.querySelectorAll('#tableList li').forEach(li => {
        li.classList.toggle('active', li.textContent === table);
    });

    showPanel('tableArea');
    document.getElementById('tableTitle').textContent = table;
    document.getElementById('mensagem').className = 'mensagem hidden';

    Promise.all([
        apiFetch(`${API}?action=describe_table&table=${encodeURIComponent(table)}`),
        apiFetch(`${API}?action=list_records&table=${encodeURIComponent(table)}`)
    ]).then(([colData, recData]) => {
        if (!colData.sucesso) { mostrarMensagem(colData.mensagem, false); return; }
        if (!recData.sucesso) { mostrarMensagem(recData.mensagem, false); return; }

        currentColumns = colData.data;
        currentPkCol   = (currentColumns.find(c => c.COLUMN_KEY === 'PRI') || {}).COLUMN_NAME || null;

        renderTable(recData.data);
    }).catch(() => mostrarMensagem('Erro de comunicação com o servidor.', false));
}

// ── Renderizar tabela ──────────────────────────────────────────────────────
function renderTable(rows) {
    const head = document.getElementById('tableHead');
    const body = document.getElementById('tableBody');

    currentRows = rows;

    head.innerHTML = currentColumns.map(c => `<th>${c.COLUMN_NAME}</th>`).join('') + '<th>Ações</th>';

    if (!rows.length) {
        body.innerHTML = `<tr class="empty-row"><td colspan="${currentColumns.length + 1}">Nenhum registro encontrado.</td></tr>`;
        return;
    }

    body.innerHTML = rows.map((row, idx) => {
        const cells = currentColumns.map(c =>
            `<td title="${escHtml(row[c.COLUMN_NAME])}">${escHtml(row[c.COLUMN_NAME])}</td>`
        ).join('');

        const pkVal = currentPkCol ? row[currentPkCol] : null;
        const actions = pkVal !== null
            ? `<td class="acoes">
                 <button class="btn-editar" onclick="editarRegistro(${idx})">Editar</button>
                 <button class="btn-excluir" onclick="excluirRegistro('${escHtml(pkVal)}')">Excluir</button>
               </td>`
            : `<td class="acoes"><em>sem PK</em></td>`;

        return `<tr>${cells}${actions}</tr>`;
    }).join('');
}

// ── Modal ──────────────────────────────────────────────────────────────────
function abrirModal(titulo) {
    editingRow = null;
    document.getElementById('modalTitulo').textContent = titulo || 'Novo Registro';
    document.getElementById('formFields').innerHTML = buildFormFields(null);
    document.getElementById('modal').classList.remove('hidden');
    document.getElementById('overlay').classList.remove('hidden');
}

function fecharModal() {
    document.getElementById('modal').classList.add('hidden');
    document.getElementById('overlay').classList.add('hidden');
    editingRow = null;
}

function editarRegistro(idx) {
    const row = currentRows[idx];
    editingRow = row;
    document.getElementById('modalTitulo').textContent = 'Editar Registro';
    document.getElementById('formFields').innerHTML = buildFormFields(row);
    document.getElementById('modal').classList.remove('hidden');
    document.getElementById('overlay').classList.remove('hidden');
}

// ── Campos do formulário ───────────────────────────────────────────────────
function buildFormFields(rowData) {
    const isEdit = rowData !== null;

    return currentColumns.map(col => {
        const name      = col.COLUMN_NAME;
        const isAutoInc = col.EXTRA.toLowerCase().includes('auto_increment');
        const dataType  = col.DATA_TYPE.toLowerCase();
        const value     = isEdit ? (rowData[name] ?? '') : '';
        const required  = col.IS_NULLABLE === 'NO' && !isAutoInc ? ' *' : '';

        if (isAutoInc && !isEdit) return '';

        let input;
        if (isAutoInc && isEdit) {
            input = `<input type="text" name="${name}" value="${escHtml(value)}" readonly class="readonly">`;
        } else if (['text', 'longtext', 'mediumtext', 'tinytext'].includes(dataType)) {
            input = `<textarea name="${name}" rows="3">${escHtml(value)}</textarea>`;
        } else if (['int', 'bigint', 'smallint', 'tinyint', 'mediumint'].includes(dataType)) {
            input = `<input type="number" name="${name}" value="${escHtml(value)}">`;
        } else if (['datetime', 'timestamp'].includes(dataType)) {
            const dtVal = value ? String(value).replace(' ', 'T').substring(0, 16) : '';
            input = `<input type="datetime-local" name="${name}" value="${escHtml(dtVal)}">`;
        } else if (dataType === 'date') {
            input = `<input type="date" name="${name}" value="${escHtml(value)}">`;
        } else {
            input = `<input type="text" name="${name}" value="${escHtml(value)}">`;
        }

        return `<div class="field-group">
                    <label>${name}${required}</label>
                    ${input}
                </div>`;
    }).join('');
}

// ── Salvar registro ────────────────────────────────────────────────────────
function salvarRegistro(e) {
    e.preventDefault();

    const formData = new FormData(document.getElementById('formRecord'));
    formData.append('__table', currentTable);

    let action;
    if (editingRow && currentPkCol) {
        action = 'update_record';
        formData.append('__pk_col', currentPkCol);
        formData.append('__pk_val', editingRow[currentPkCol]);
    } else {
        action = 'create_record';
    }
    formData.append('action', action);

    apiFetch(API, { method: 'POST', body: formData })
        .then(data => {
            mostrarMensagem(data.mensagem, data.sucesso);
            if (data.sucesso) {
                fecharModal();
                recarregarRegistros();
            }
        })
        .catch(() => mostrarMensagem('Erro de comunicação com o servidor.', false));
}

// ── Excluir registro ───────────────────────────────────────────────────────
function excluirRegistro(pkVal) {
    if (!confirm(`Excluir o registro com ${currentPkCol} = "${pkVal}"?`)) return;

    const formData = new FormData();
    formData.append('action',    'delete_record');
    formData.append('__table',   currentTable);
    formData.append('__pk_col',  currentPkCol);
    formData.append('__pk_val',  pkVal);

    apiFetch(API, { method: 'POST', body: formData })
        .then(data => {
            mostrarMensagem(data.mensagem, data.sucesso);
            if (data.sucesso) recarregarRegistros();
        })
        .catch(() => mostrarMensagem('Erro de comunicação com o servidor.', false));
}

// ── Recarregar registros ───────────────────────────────────────────────────
function recarregarRegistros() {
    apiFetch(`${API}?action=list_records&table=${encodeURIComponent(currentTable)}`)
        .then(data => {
            if (data.sucesso) renderTable(data.data);
        });
}

// ── Mensagem de feedback (área de tabela) ──────────────────────────────────
function mostrarMensagem(texto, sucesso) {
    const el = document.getElementById('mensagem');
    el.textContent = texto;
    el.className   = 'mensagem ' + (sucesso ? 'sucesso' : 'erro');
    clearTimeout(el._timeout);
    el._timeout = setTimeout(() => { el.className = 'mensagem hidden'; }, 4000);
}

// ── Proteção contra XSS ───────────────────────────────────────────────────
function escHtml(val) {
    return String(val ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}
