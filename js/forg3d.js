const FORG3D = (() => {
    const moeda = v => v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    const num = v => parseFloat(String(v).replace(',', '.')) || 0;
    const r2 = v => Math.round(v * 100) / 100;
    const $ = (sel, raiz = document) => raiz.querySelector(sel);
    const $$ = (sel, raiz = document) => [...raiz.querySelectorAll(sel)];
    const escapar = t => String(t ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    function faiscas() {
        const caixa = $('.f-faiscas');
        if (!caixa || matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        const criar = () => {
            const f = document.createElement('span');
            f.className = 'f-faisca';
            const tam = 2 + Math.random() * 3;
            f.style.left = (30 + Math.random() * 40) + '%';
            f.style.width = f.style.height = tam + 'px';
            f.style.setProperty('--dx', ((Math.random() - .5) * 300) + 'px');
            f.style.animationDuration = (2.5 + Math.random() * 3) + 's';
            caixa.appendChild(f);
            f.addEventListener('animationend', () => f.remove());
        };
        setInterval(criar, 140);
    }

    function mostrarSenha() {
        $$('[data-mostrar-senha]').forEach(btn => btn.addEventListener('click', () => {
            const campo = $(btn.dataset.mostrarSenha);
            const ver = campo.type === 'password';
            campo.type = ver ? 'text' : 'password';
            btn.innerHTML = `<i class="bi ${ver ? 'bi-eye-slash' : 'bi-eye'}"></i>`;
        }));
    }

    function confirmarSenhas() {
        $$('[data-igual-a]').forEach(campo => {
            const outro = $(campo.dataset.igualA);
            const checar = () => campo.setCustomValidity(campo.value && campo.value !== outro.value ? 'As senhas não conferem.' : '');
            campo.addEventListener('input', checar);
            outro.addEventListener('input', checar);
        });
    }

    function confirmarEnvios() {
        $$('form[data-confirmar]').forEach(form => form.addEventListener('submit', e => {
            const msg = form.dataset.confirmar;
            if (msg && !confirm(msg)) e.preventDefault();
        }));
    }

    function mascaras() {
        const soNum = v => v.replace(/\D/g, '');
        $$('[data-mascara="telefone"]').forEach(c => c.addEventListener('input', () => {
            const n = soNum(c.value).slice(0, 11);
            c.value = n.length > 10 ? n.replace(/(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3')
                : n.replace(/(\d{2})(\d{0,4})(\d{0,4})/, (m, a, b, d) => `(${a}) ${b}${d ? '-' + d : ''}`).replace(/\) $/, ')');
        }));
        $$('[data-mascara="documento"]').forEach(c => c.addEventListener('input', () => {
            const tipo = $('#tipo')?.value || 'PF';
            const n = soNum(c.value).slice(0, tipo === 'PJ' ? 14 : 11);
            c.value = tipo === 'PJ'
                ? n.replace(/^(\d{2})(\d)/, '$1.$2').replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3').replace(/\.(\d{3})(\d)/, '.$1/$2').replace(/(\d{4})(\d)/, '$1-$2')
                : n.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        }));
    }

    function previewImagem() {
        $$('[data-preview]').forEach(input => input.addEventListener('change', () => {
            const arq = input.files[0];
            if (arq) $(input.dataset.preview).src = URL.createObjectURL(arq);
        }));
    }

    function chipsPreencher() {
        $$('[data-preencher]').forEach(b => b.addEventListener('click', () => {
            $(b.dataset.preencher).value = b.dataset.valor;
        }));
    }

    function graficosDashboard(d) {
        if (typeof Chart === 'undefined') return;
        Chart.defaults.color = '#a3abb5';
        Chart.defaults.font.family = 'Inter, sans-serif';
        Chart.defaults.borderColor = 'rgba(255,255,255,.06)';

        const ctx = $('#graficoFaturamento');
        if (ctx) {
            const g = ctx.getContext('2d');
            const brasa = g.createLinearGradient(0, 0, 0, 280);
            brasa.addColorStop(0, '#ffb347');
            brasa.addColorStop(1, '#c2410c');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: d.meses,
                    datasets: [
                        { label: 'Faturamento', data: d.faturamento, backgroundColor: brasa, borderRadius: 6, maxBarThickness: 38 },
                        { label: 'Custo', data: d.custos, backgroundColor: 'rgba(163,171,181,.35)', borderRadius: 6, maxBarThickness: 38 },
                    ],
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: { label: c => `${c.dataset.label}: ${moeda(c.raw)}` } } },
                    scales: { y: { beginAtZero: true, ticks: { callback: v => 'R$ ' + v } }, x: { grid: { display: false } } },
                },
            });
        }

        const ctx2 = $('#graficoMaterial');
        if (ctx2 && d.gramas.length) {
            new Chart(ctx2, {
                type: 'doughnut',
                data: { labels: d.materiais, datasets: [{ data: d.gramas, backgroundColor: d.cores, borderColor: '#14181d', borderWidth: 3 }] },
                options: {
                    maintainAspectRatio: false, cutout: '62%',
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } }, tooltip: { callbacks: { label: c => `${c.label}: ${(c.raw / 1000).toFixed(2).replace('.', ',')} kg` } } },
                },
            });
        }
    }

    function orcamento(d) {
        const lista = $('#listaItens');
        const tpl = $('#tplItem');
        const form = $('#formOrcamento');
        const mat = id => d.materiais.find(m => m.id === +id);
        const imp = id => d.impressoras.find(p => p.id === +id);
        const mod = id => d.modelos.find(m => m.id === +id);

        function renumerar() {
            $$('[data-item]', lista).forEach((linha, i) => {
                $('[data-numero]', linha).textContent = i + 1;
                $$('[data-campo]', linha).forEach(c => c.name = `itens[${i}][${c.dataset.campo}]`);
            });
        }

        function adicionar(preset = {}) {
            const linha = tpl.content.firstElementChild.cloneNode(true);
            lista.appendChild(linha);
            Object.entries(preset).forEach(([campo, valor]) => {
                const c = $(`[data-campo="${campo}"]`, linha);
                if (c && valor !== undefined && valor !== null && valor !== 0) c.value = valor;
            });
            renumerar();
            calcular();
            return linha;
        }

        lista.addEventListener('click', e => {
            if (e.target.closest('[data-remover]')) {
                if ($$('[data-item]', lista).length === 1) return;
                e.target.closest('[data-item]').remove();
                renumerar();
                calcular();
            }
        });

        lista.addEventListener('change', e => {
            if (e.target.dataset.campo === 'modelo_id') {
                const linha = e.target.closest('[data-item]');
                const m = mod(e.target.value);
                if (m) {
                    $('[data-campo="descricao"]', linha).value = m.nome;
                    $('[data-campo="peso_g"]', linha).value = m.peso_g;
                    $('[data-campo="tempo_h"]', linha).value = m.tempo_h;
                    if (m.material_id && mat(m.material_id)) $('[data-campo="material_id"]', linha).value = m.material_id;
                }
            }
            calcular();
        });
        lista.addEventListener('input', calcular);
        $('#margem').addEventListener('input', calcular);
        $('#taxa_falha').addEventListener('input', calcular);
        $('#btnAddItem').addEventListener('click', () => adicionar());

        function calcular() {
            const margem = num($('#margem').value), falha = num($('#taxa_falha').value);
            const t = { material: 0, energia: 0, maquina: 0, falha: 0, custo: 0, total: 0, pecas: 0, gramas: 0, horas: 0 };
            const consumo = {};

            $$('[data-item]', lista).forEach(linha => {
                const v = campo => $(`[data-campo="${campo}"]`, linha).value;
                const m = mat(v('material_id')), p = imp(v('impressora_id'));
                const qtd = Math.max(1, parseInt(v('quantidade')) || 1), peso = num(v('peso_g')), horas = num(v('tempo_h'));
                if (!m || !p) return;

                const cm = r2(peso / 1000 * m.preco_kg);
                const ce = r2(horas * p.potencia_w / 1000 * d.tarifa);
                const cq = r2(horas * (p.valor / Math.max(1, p.vida)));
                const cu = r2((cm + ce + cq) * (1 + falha / 100));
                const pu = r2(cu * (1 + margem / 100));

                t.material += cm * qtd; t.energia += ce * qtd; t.maquina += cq * qtd;
                t.custo += cu * qtd; t.total += pu * qtd;
                t.pecas += qtd; t.gramas += peso * qtd; t.horas += horas * qtd;
                consumo[m.id] = (consumo[m.id] || 0) + peso * qtd;
                $('[data-subtotal]', linha).textContent = moeda(pu * qtd);
            });
            t.falha = t.custo - (t.material + t.energia + t.maquina);

            $('#rMaterial').textContent = moeda(t.material);
            $('#rEnergia').textContent = moeda(t.energia);
            $('#rMaquina').textContent = moeda(t.maquina);
            $('#rFalha').textContent = moeda(t.falha);
            $('#rCusto').textContent = moeda(t.custo);
            $('#rLucro').textContent = moeda(t.total - t.custo);
            $('#rTotal').textContent = moeda(t.total);
            $('#rInfo').textContent = `${t.pecas} peça(s) · ${Math.round(t.gramas)} g · ${t.horas.toFixed(1).replace('.', ',')} h de máquina`;

            const faltas = Object.entries(consumo).filter(([id, g]) => g > mat(id).estoque_g)
                .map(([id, g]) => `${mat(id).nome}: precisa ${Math.round(g)} g, tem ${Math.round(mat(id).estoque_g)} g`);
            $('#rAvisoEstoque').innerHTML = faltas.length
                ? `<div class="alert alert-warning py-2 px-3 mb-0"><i class="bi bi-exclamation-triangle-fill me-1"></i>Estoque insuficiente<br>${faltas.map(escapar).join('<br>')}</div>`
                : (t.pecas ? '<span style="color:var(--f-verde)"><i class="bi bi-check2-circle me-1"></i>Há filamento suficiente em estoque.</span>' : '');
        }

        form.addEventListener('submit', e => {
            if (!$$('[data-item]', lista).length) { e.preventDefault(); adicionar(); }
        });

        const inicial = mod(d.modeloInicial);
        adicionar(inicial ? { modelo_id: inicial.id, descricao: inicial.nome, peso_g: inicial.peso_g, tempo_h: inicial.tempo_h, material_id: inicial.material_id } : {});

        const btnIA = $('#btnIA'), txtIA = $('#iaDescricao'), saida = $('#iaResultado');
        $$('[data-exemplo]').forEach(b => b.addEventListener('click', () => { txtIA.value = b.dataset.exemplo; btnIA.click(); }));
        txtIA.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); btnIA.click(); } });

        btnIA.addEventListener('click', async () => {
            const descricao = txtIA.value.trim();
            if (descricao.length < 3) { txtIA.focus(); return; }
            btnIA.disabled = true;
            saida.innerHTML = `<div class="text-center py-3"><div class="f-imprimindo"><span></span><span></span><span></span><span></span><span></span></div>
                <div class="small f-muted">Imprimindo a resposta camada por camada...</div></div>`;

            try {
                const dados = new FormData();
                dados.append('descricao', descricao);
                dados.append('csrf', form.querySelector('[name="csrf"]').value);
                const resp = await fetch('../processamento/ia.php', { method: 'POST', body: dados });
                const json = await resp.json();
                if (json.erro) throw new Error(json.erro);
                mostrarSugestao(json, descricao);
            } catch (err) {
                saida.innerHTML = `<div class="alert alert-danger mb-0">${escapar(err.message || 'Falha ao consultar a IA.')}</div>`;
            } finally {
                btnIA.disabled = false;
            }
        });

        function mostrarSugestao(json, descricao) {
            const s = json.sugestao;
            const opcoes = s.opcoes || [];
            const selo = json.modo === 'gemini'
                ? '<span class="f-ia-selo conectado">Resposta do Gemini</span>'
                : '<span class="f-ia-selo demo">Modo demonstração (regras locais)</span>';
            const cards = opcoes.map((o, i) => `
                <div class="col-md-4">
                    <div class="f-ia-opcao h-100${i === 0 ? ' f-ia-melhor' : ''}">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                            <strong class="${i === 0 ? 'f-texto-brasa' : ''}">${escapar(o.material)}</strong>
                            ${i === 0 ? '<span class="f-chip">mais indicado</span>' : ''}
                        </div>
                        <div class="small f-muted mb-2">${escapar(o.indicado_para)}</div>
                        <p class="small mb-2">${escapar(o.motivo)}</p>
                        <ul class="small f-muted list-unstyled mb-3 d-flex flex-column gap-1">
                            <li><i class="bi bi-grid-3x3 me-1"></i>${escapar(o.preenchimento)} · ${escapar(o.paredes)} paredes</li>
                            <li><i class="bi bi-layers me-1"></i>camada ${escapar(o.camada)} · suportes: ${escapar(o.suportes)}</li>
                            <li><i class="bi bi-thermometer-half me-1"></i>${escapar(o.temperatura)}</li>
                        </ul>
                        <button type="button" class="btn btn-sm w-100 ${i === 0 ? 'btn-forja' : 'btn-aco'}" data-usar="${i}"${o.material_id ? '' : ' disabled'}>
                            <i class="bi bi-box-arrow-in-down me-1"></i>${o.material_id ? 'Usar esta' : 'Sem estoque'}
                        </button>
                    </div>
                </div>`).join('');
            const links = (s.modelos || []).map(m =>
                `<a class="btn btn-aco btn-sm" href="${escapar(m.url)}" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right me-1"></i>${escapar(m.site)}</a>`).join('');

            saida.innerHTML = `
                ${json.aviso ? `<div class="alert alert-warning py-2 small">${escapar(json.aviso)}</div>` : ''}
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1">
                    <strong class="fs-5">${escapar(s.peca)}</strong>${selo}
                </div>
                <p class="small f-muted mb-3">${escapar(s.uso)} · estimativa ${escapar(s.peso_estimado_g)} g e ${escapar(s.tempo_estimado_h)} h por unidade</p>
                <div class="row g-2 mb-3">${cards}</div>
                <div class="f-ia-modelos">
                    <div class="small mb-2"><i class="bi bi-search me-1"></i>Modelos prontos para baixar · busca por <strong>${escapar(s.termos_busca)}</strong></div>
                    <div class="d-flex flex-wrap gap-2">${links}</div>
                </div>
                ${s.observacoes ? `<p class="small f-muted mt-3 mb-0"><i class="bi bi-lightbulb me-1"></i>${escapar(s.observacoes)}</p>` : ''}`;

            $$('[data-usar]', saida).forEach(btn => btn.addEventListener('click', () => {
                const o = opcoes[+btn.dataset.usar];
                const linha = $('[data-item]', lista);
                const set = (campo, valor) => { if (valor) $(`[data-campo="${campo}"]`, linha).value = valor; };
                if (o.material_id && mat(o.material_id)) set('material_id', o.material_id);
                if (!$('[data-campo="descricao"]', linha).value) set('descricao', String(s.peca || descricao).slice(0, 150));
                if (!num($('[data-campo="peso_g"]', linha).value)) set('peso_g', num(s.peso_estimado_g));
                if (!num($('[data-campo="tempo_h"]', linha).value)) set('tempo_h', num(s.tempo_estimado_h));
                $('#sugestao_ia').value =
                    `Material: ${o.material} — ${o.motivo}\nPreenchimento ${o.preenchimento}, camada ${o.camada}, ${o.paredes} paredes, suportes: ${o.suportes}, temperatura ${o.temperatura}.` +
                    (s.observacoes ? `\nDica: ${s.observacoes}` : '') +
                    (json.modo === 'demo' ? '\n(gerado em modo demonstração)' : '\n(gerado pelo Gemini)');
                $$('[data-usar]', saida).forEach(b => b.closest('.f-ia-opcao').classList.remove('f-ia-escolhida'));
                btn.closest('.f-ia-opcao').classList.add('f-ia-escolhida');
                calcular();
                linha.scrollIntoView({ behavior: 'smooth', block: 'center' });
                linha.style.boxShadow = '0 0 0 2px rgba(111,183,255,.6)';
                setTimeout(() => linha.style.boxShadow = '', 1500);
            }));
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        faiscas();
        mostrarSenha();
        confirmarSenhas();
        confirmarEnvios();
        mascaras();
        previewImagem();
        chipsPreencher();
    });

    return { graficosDashboard, orcamento };
})();
