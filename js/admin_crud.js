const API = '../controller/crud_controller.php';

// Wrapper de fetch que intercepta respostas de sessão expirada.
// Se o servidor retornar expirado:true, redireciona pro login com aviso.
function apiFetch(url, options) {
    return fetch(url, options)
        .then(r => r.json())
        .then(data => {
            if (data.expirado) {
                window.location.href = 'admin_login.php?timeout=1';
                throw new Error('expirado'); // Interrompe a cadeia de .then()
            }
            return data;
        });
}

// ── Estado da sessão ───────────────────────────────────────────────────────
// Variáveis globais que guardam o contexto atual do painel
let currentTable   = null;   // tabela selecionada na sidebar
let currentColumns = [];     // colunas dessa tabela (vem do INFORMATION_SCHEMA)
let currentPkCol   = null;   // nome da coluna que é chave primária
let editingRow     = null;   // null = modo criação, objeto = modo edição

// ── Inicialização ──────────────────────────────────────────────────────────
// Quando a página carrega, busca as tabelas disponíveis e popula a sidebar
document.addEventListener('DOMContentLoaded', () => {
    apiFetch(`${API}?action=list_tables`)
        .then(data => {
            const list = document.getElementById('tableList');
            if (!data.sucesso || !data.data.length) {
                list.innerHTML = '<li class="loading">Nenhuma tabela encontrada.</li>';
                return;
            }
            // Cria um item clicável na sidebar pra cada tabela
            list.innerHTML = data.data
                .map(t => `<li onclick="selectTable('${t}')">${t}</li>`)
                .join('');
        })
        .catch(() => {
            document.getElementById('tableList').innerHTML =
                '<li class="loading">Erro ao carregar tabelas.</li>';
        });
});

// ── Selecionar tabela ──────────────────────────────────────────────────────
// Chamado quando o usuário clica em uma tabela na sidebar
function selectTable(table) {
    currentTable = table;
    editingRow   = null;

    // Destaca o item ativo na sidebar
    document.querySelectorAll('#tableList li').forEach(li => {
        li.classList.toggle('active', li.textContent === table);
    });

    // Mostra a área de conteúdo e esconde o placeholder inicial
    document.getElementById('placeholder').classList.add('hidden');
    document.getElementById('tableArea').classList.remove('hidden');
    document.getElementById('tableTitle').textContent = table;
    document.getElementById('mensagem').className = 'mensagem hidden';

    // Carrega a estrutura da tabela e os registros ao mesmo tempo (mais rápido)
    Promise.all([
        apiFetch(`${API}?action=describe_table&table=${encodeURIComponent(table)}`),
        apiFetch(`${API}?action=list_records&table=${encodeURIComponent(table)}`)
    ]).then(([colData, recData]) => {
        if (!colData.sucesso) { mostrarMensagem(colData.mensagem, false); return; }
        if (!recData.sucesso) { mostrarMensagem(recData.mensagem, false); return; }

        currentColumns = colData.data;
        // Procura qual coluna é a PK — usada pra identificar registros nas ações de editar/excluir
        currentPkCol   = (currentColumns.find(c => c.COLUMN_KEY === 'PRI') || {}).COLUMN_NAME || null;

        renderTable(recData.data);
    }).catch(() => mostrarMensagem('Erro de comunicação com o servidor.', false));
}

// ── Renderizar tabela ──────────────────────────────────────────────────────
// Monta o HTML da tabela com os dados recebidos do servidor
function renderTable(rows) {
    const head = document.getElementById('tableHead');
    const body = document.getElementById('tableBody');

    // Cabeçalhos gerados a partir das colunas da tabela + coluna de ações
    head.innerHTML = currentColumns.map(c => `<th>${c.COLUMN_NAME}</th>`).join('') + '<th>Ações</th>';

    // Se não tiver registros, mostra uma linha informativa
    if (!rows.length) {
        body.innerHTML = `<tr class="empty-row"><td colspan="${currentColumns.length + 1}">Nenhum registro encontrado.</td></tr>`;
        return;
    }

    body.innerHTML = rows.map(row => {
        // Célula por célula, com escaping pra evitar XSS
        const cells = currentColumns.map(c =>
            `<td title="${escHtml(row[c.COLUMN_NAME])}">${escHtml(row[c.COLUMN_NAME])}</td>`
        ).join('');

        const pkVal = currentPkCol ? row[currentPkCol] : null;
        // Só exibe os botões de ação se a tabela tiver uma chave primária
        const actions = pkVal !== null
            ? `<td class="acoes">
                 <button class="btn-editar" onclick='editarRegistro(${JSON.stringify(row)})'>Editar</button>
                 <button class="btn-excluir" onclick="excluirRegistro('${escHtml(pkVal)}')">Excluir</button>
               </td>`
            : `<td class="acoes"><em>sem PK</em></td>`;

        return `<tr>${cells}${actions}</tr>`;
    }).join('');
}

// ── Abrir e fechar modal ───────────────────────────────────────────────────

// Abre o modal no modo criação (formulário em branco)
function abrirModal(titulo) {
    editingRow = null;
    document.getElementById('modalTitulo').textContent = titulo || 'Novo Registro';
    document.getElementById('formFields').innerHTML = buildFormFields(null);
    document.getElementById('modal').classList.remove('hidden');
    document.getElementById('overlay').classList.remove('hidden');
}

// Fecha o modal e limpa o estado de edição
function fecharModal() {
    document.getElementById('modal').classList.add('hidden');
    document.getElementById('overlay').classList.add('hidden');
    editingRow = null;
}

// Abre o modal no modo edição, pré-preenchendo os campos com os dados da linha
function editarRegistro(row) {
    editingRow = row;
    document.getElementById('modalTitulo').textContent = 'Editar Registro';
    document.getElementById('formFields').innerHTML = buildFormFields(row);
    document.getElementById('modal').classList.remove('hidden');
    document.getElementById('overlay').classList.remove('hidden');
}

// ── Montar campos do formulário dinamicamente ──────────────────────────────
// Gera os inputs do formulário com base no tipo de dado de cada coluna
function buildFormFields(rowData) {
    const isEdit = rowData !== null;

    return currentColumns.map(col => {
        const name      = col.COLUMN_NAME;
        const isAutoInc = col.EXTRA.toLowerCase().includes('auto_increment');
        const dataType  = col.DATA_TYPE.toLowerCase();
        const value     = isEdit ? (rowData[name] ?? '') : '';
        // Marca campos obrigatórios com asterisco (exceto auto_increment)
        const required  = col.IS_NULLABLE === 'NO' && !isAutoInc ? ' *' : '';

        // Auto_increment: omite no formulário de criação, mostra como readonly ao editar
        if (isAutoInc && !isEdit) return '';

        let input;
        if (isAutoInc && isEdit) {
            // Campo de ID: visível mas não editável
            input = `<input type="text" name="${name}" value="${escHtml(value)}" readonly class="readonly">`;
        } else if (['text', 'longtext', 'mediumtext', 'tinytext'].includes(dataType)) {
            // Textos longos ganham uma área de texto redimensionável
            input = `<textarea name="${name}" rows="3">${escHtml(value)}</textarea>`;
        } else if (['int', 'bigint', 'smallint', 'tinyint', 'mediumint'].includes(dataType)) {
            // Inteiros usam input numérico (evita letras e facilita validação)
            input = `<input type="number" name="${name}" value="${escHtml(value)}">`;
        } else if (['datetime', 'timestamp'].includes(dataType)) {
            // datetime-local precisa do formato "YYYY-MM-DDTHH:MM" — convertemos aqui
            const dtVal = value ? String(value).replace(' ', 'T').substring(0, 16) : '';
            input = `<input type="datetime-local" name="${name}" value="${escHtml(dtVal)}">`;
        } else if (dataType === 'date') {
            input = `<input type="date" name="${name}" value="${escHtml(value)}">`;
        } else {
            // Qualquer outro tipo (varchar, char, enum, etc.) usa texto simples
            input = `<input type="text" name="${name}" value="${escHtml(value)}">`;
        }

        return `<div class="field-group">
                    <label>${name}${required}</label>
                    ${input}
                </div>`;
    }).join('');
}

// ── Salvar (criar ou editar) ───────────────────────────────────────────────
// Detecta automaticamente se é criação ou edição com base em editingRow
function salvarRegistro(e) {
    e.preventDefault();

    const formData = new FormData(document.getElementById('formRecord'));
    formData.append('__table', currentTable);

    let action;
    if (editingRow && currentPkCol) {
        // Modo edição: manda a PK pra o servidor saber qual registro atualizar
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
                recarregarRegistros(); // Atualiza a tabela sem recarregar a página
            }
        })
        .catch(() => mostrarMensagem('Erro de comunicação com o servidor.', false));
}

// ── Excluir registro ───────────────────────────────────────────────────────
// Pede confirmação antes de deletar — uma vez confirmado, sem volta
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
// Busca os dados atualizados sem recarregar a página inteira
function recarregarRegistros() {
    apiFetch(`${API}?action=list_records&table=${encodeURIComponent(currentTable)}`)
        .then(data => {
            if (data.sucesso) renderTable(data.data);
        });
}

// ── Mensagem de feedback ───────────────────────────────────────────────────
// Exibe uma mensagem de sucesso ou erro por 4 segundos e some sozinha
function mostrarMensagem(texto, sucesso) {
    const el = document.getElementById('mensagem');
    el.textContent = texto;
    el.className   = 'mensagem ' + (sucesso ? 'sucesso' : 'erro');
    clearTimeout(el._timeout);
    el._timeout = setTimeout(() => { el.className = 'mensagem hidden'; }, 4000);
}

// ── Proteção contra XSS ───────────────────────────────────────────────────
// Escapa caracteres especiais antes de inserir qualquer valor no HTML.
// Sem isso, dados do banco com < > & " ' poderiam quebrar ou injetar código.
function escHtml(val) {
    return String(val ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}
