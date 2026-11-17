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

    return { orcamento };
})();
