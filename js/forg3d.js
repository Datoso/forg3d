(() => {
    const $ = (sel, raiz = document) => raiz.querySelector(sel);
    const $$ = (sel, raiz = document) => [...raiz.querySelectorAll(sel)];

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

    document.addEventListener('DOMContentLoaded', () => {
        faiscas();
        mostrarSenha();
        confirmarSenhas();
        confirmarEnvios();
        mascaras();
        previewImagem();
    });
})();
