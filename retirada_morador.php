<?php
require_once 'conexao.php';
$publica = true;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retirada de Encomenda - Autoatendimento | PortariaControl</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        :root {
            --cor-principal: #0f766e;
            --cor-principal-esc: #115e59;
            --cor-fundo: #f0fdfa;
            --cor-card: #ffffff;
        }
        .hero-topo {
            background: linear-gradient(135deg, var(--cor-principal) 0%, #0ea5e9 100%);
            color: #fff;
            padding: 28px 0 80px 0;
            border-radius: 0 0 30px 30px;
            box-shadow: 0 10px 30px rgba(15, 118, 110, 0.18);
        }
        .hero-topo .titulo {
            font-weight: 800;
            letter-spacing: -0.5px;
            font-size: 2rem;
        }
        .card-principal {
            background: var(--cor-card);
            border: none;
            border-radius: 22px;
            box-shadow: 0 20px 50px -12px rgba(15, 23, 42, 0.15);
            margin-top: -50px;
            padding: 30px;
        }
        @media (max-width: 576px) {
            .card-principal { padding: 20px 16px; }
            .hero-topo .titulo { font-size: 1.5rem; }
        }
        .step-badge {
            width: 38px; height: 38px; border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
            font-weight: 700; color: #fff;
            background: var(--cor-principal);
        }
        .step-badge.off { background: #cbd5e1; color: #64748b; }
        .step-bar { height: 4px; background: #e2e8f0; border-radius: 999px; flex: 1; margin: 0 10px; }
        .step-bar.on { background: var(--cor-principal); }

        .btn-principal {
            background: linear-gradient(135deg, var(--cor-principal), var(--cor-principal-esc));
            border: none; color: #fff; font-weight: 700;
            padding: 14px 24px; border-radius: 14px;
            transition: all .2s ease;
            box-shadow: 0 6px 16px rgba(15, 118, 110, 0.25);
        }
        .btn-principal:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 22px rgba(15, 118, 110, 0.3);
            color: #fff;
        }
        .btn-principal:disabled { opacity: .5; box-shadow: none; transform: none; }

        .input-grande {
            padding: 18px 18px; font-size: 1.08rem;
            border-radius: 14px; border: 2px solid #cbd5e1;
            transition: all .15s ease;
        }
        .input-grande:focus {
            border-color: var(--cor-principal);
            box-shadow: 0 0 0 5px rgba(15, 118, 110, 0.15);
            outline: none;
        }

        .enc-card {
            border: 2px solid #e2e8f0; border-radius: 14px;
            padding: 16px; transition: all .15s ease;
            cursor: pointer; margin-bottom: 12px;
            background: #fff;
        }
        .enc-card:hover { border-color: #5eead4; background: #f0fdfa; }
        .enc-card.selected {
            border-color: var(--cor-principal);
            background: #ecfeff;
            box-shadow: 0 6px 18px rgba(14, 165, 233, 0.15);
        }
        .enc-card input[type="checkbox"] { width: 20px; height: 20px; cursor: pointer; }
        .tag-origem {
            display: inline-block; padding: 4px 10px; border-radius: 999px;
            background: #f1f5f9; color: #334155; font-size: 0.78rem; font-weight: 600;
        }
        .cod-etiqueta {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-weight: 700; color: #0f172a; background: #f8fafc;
            padding: 4px 10px; border-radius: 8px;
        }
        .morador-card {
            background: linear-gradient(135deg, #ecfeff 0%, #f0fdf4 100%);
            border: 2px solid #5eead4; border-radius: 18px;
            padding: 20px; margin-bottom: 20px;
        }
        .aviso-erro {
            background: #fef2f2; color: #991b1b;
            border-left: 4px solid #ef4444;
            border-radius: 10px; padding: 12px 16px;
        }
        .aviso-sucesso {
            background: #ecfdf5; color: #065f46;
            border-left: 4px solid #10b981;
            border-radius: 10px; padding: 12px 16px;
        }
        .comprovante {
            background: #fffbeb; border: 2px dashed #f59e0b;
            border-radius: 18px; padding: 24px;
        }
        .qr-dummy {
            width: 120px; height: 120px; margin: 0 auto;
            background: repeating-conic-gradient(#0f172a 0% 25%, #fff 0% 50%) 50% / 14px 14px;
            border-radius: 10px;
        }
        .protocolo {
            font-family: ui-monospace, monospace; font-weight: 800;
            font-size: 1.4rem; color: var(--cor-principal-esc);
            letter-spacing: 1.5px;
        }
        .tela { display: none; }
        .tela.ativa { display: block; animation: fadeIn .3s ease; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .spinner-sm {
            width: 18px; height: 18px; border: 2px solid #fff;
            border-top-color: transparent; border-radius: 50%;
            display: inline-block; animation: spin .7s linear infinite;
            vertical-align: middle; margin-right: 8px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

<header class="hero-topo">
    <div class="container d-flex align-items-center justify-content-between">
        <a href="index.php" class="text-white text-decoration-none d-flex align-items-center gap-2">
            <i class="bi bi-box-seam fs-3" style="color:#fde68a"></i>
            <span class="fw-bold fs-5">Portaria<strong class="text-warning">Control</strong></span>
        </a>
        <a href="index.php" class="btn btn-light btn-sm rounded-pill px-3 fw-semibold" style="color:var(--cor-principal-esc)">
            <i class="bi bi-house-door me-1"></i> Painel
        </a>
    </div>
    <div class="container mt-4">
        <h1 class="titulo mb-1"><i class="bi bi-box2-heart me-2"></i>Retirada de Encomenda</h1>
        <p class="opacity-90 mb-0">Autoatendimento rápido e seguro para retirar seu pacote.</p>
    </div>
</header>

<main class="container">

    <div class="card-principal">

        <!-- Barra de progresso -->
        <div class="d-flex align-items-center mb-4">
            <span class="step-badge" id="barra1">1</span>
            <div class="step-bar" id="barraBar1"></div>
            <span class="step-badge off" id="barra2">2</span>
            <div class="step-bar" id="barraBar2"></div>
            <span class="step-badge off" id="barra3">3</span>
        </div>

        <!-- ============================================================ -->
        <!-- TELA 1 -> BUSCA                                               -->
        <!-- ============================================================ -->
        <section class="tela ativa" id="tela1">
            <div class="mb-4">
                <h3 class="fw-bold text-dark mb-1"><i class="bi bi-search me-2 text-teal"></i>Encontre suas encomendas</h3>
                <p class="text-muted mb-0">Digite abaixo um dos campos para localizar seus pacotes pendentes.</p>
            </div>

            <div id="alerta1"></div>

            <form id="formBusca" onsubmit="return false;">
                <label class="form-label fw-semibold text-secondary small text-uppercase mb-2">
                    Unidade, CPF, Nome ou Código da Etiqueta
                </label>
                <div class="mb-3">
                    <input
                        type="text" id="termo" class="form-control input-grande"
                        placeholder="Ex: 22, 123.456.789-00, SS987654321BR ou Alexandre Alves..."
                        autocomplete="off" required>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col">
                        <button type="button" class="btn btn-outline-secondary w-100 rounded-pill" onclick="sugestao('22')">
                            <i class="bi bi-house me-1"></i> Unid. 22
                        </button>
                    </div>
                    <div class="col">
                        <button type="button" class="btn btn-outline-secondary w-100 rounded-pill" onclick="sugestao('SS987654321BR')">
                            <i class="bi bi-upc-scan me-1"></i> SS987654321BR
                        </button>
                    </div>
                    <div class="col">
                        <button type="button" class="btn btn-outline-secondary w-100 rounded-pill" onclick="sugestao('Alexandre')">
                            <i class="bi bi-person me-1"></i> Alexandre
                        </button>
                    </div>
                </div>

                <button type="submit" id="btnBuscar" class="btn-principal w-100" onclick="buscar()">
                    <i class="bi bi-search me-1"></i> BUSCAR ENCOMENDAS
                </button>
            </form>
        </section>

        <!-- ============================================================ -->
        <!-- TELA 2 -> CONFIRMACAO                                         -->
        <!-- ============================================================ -->
        <section class="tela" id="tela2">
            <div class="mb-3">
                <h3 class="fw-bold text-dark mb-1"><i class="bi bi-clipboard2-check me-2 text-success"></i>Confirme sua retirada</h3>
                <p class="text-muted mb-0">Verifique os dados e selecione as encomendas que deseja retirar agora.</p>
            </div>

            <div id="alerta2"></div>

            <div class="morador-card">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-white p-3 rounded-circle shadow-sm">
                        <i class="bi bi-person-fill fs-3" style="color:var(--cor-principal)"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="fw-bold text-dark mb-0" id="vMorador">—</h5>
                        <div class="row g-2 mt-1">
                            <div class="col-auto">
                                <span class="tag-origem"><i class="bi bi-house-door me-1"></i> Unidade <span id="vUnid" class="fw-bold">—</span></span>
                            </div>
                            <div class="col-auto">
                                <span class="tag-origem"><i class="bi bi-telephone me-1"></i> <span id="vTel">—</span></span>
                            </div>
                            <div class="col-auto">
                                <span class="tag-origem"><i class="bi bi-person-vcard me-1"></i> CPF <span id="vCpf" class="fw-bold">—</span></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <h6 class="fw-bold text-secondary text-uppercase small mb-3">
                <i class="bi bi-box-seam me-1"></i> Encomendas pendentes para retirada (<span id="vTotal">0</span>)
            </h6>

            <form id="formConfirm" onsubmit="return false;">
                <div id="listaEncomendas" class="mb-3"></div>

                <div class="mb-3">
                    <label class="form-label fw-semibold text-secondary small text-uppercase mb-1">
                        <i class="bi bi-pen me-1"></i> Assinatura digital (nome completo)
                    </label>
                    <input
                        type="text" id="assinatura" class="form-control input-grande"
                        placeholder="Digite seu nome completo para confirmar a retirada..." required>
                    <div class="form-text text-muted small">
                        <i class="bi bi-info-circle me-1"></i>
                        Essa assinatura fica registrada no histórico de retirada como comprovante de recebimento.
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold text-secondary small text-uppercase mb-1">
                        <i class="bi bi-person-badge me-1"></i> Porteiro responsável (opcional)
                    </label>
                    <input type="text" id="porteiro" class="form-control" value="Autoatendimento (Morador)"
                        placeholder="Deixe Autoatendimento se estiver sozinho, ou o nome do porteiro">
                </div>

                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light text-secondary fw-semibold px-4 py-3 rounded-4 w-50" onclick="mostrarTela(1)">
                        <i class="bi bi-arrow-left me-1"></i> Voltar
                    </button>
                    <button type="submit" id="btnConfirmar" class="btn-principal w-50" onclick="confirmarRetirada()">
                        <i class="bi bi-check2-circle me-1"></i> CONFIRMAR RETIRADA
                    </button>
                </div>
            </form>
        </section>

        <!-- ============================================================ -->
        <!-- TELA 3 -> COMPROVANTE                                         -->
        <!-- ============================================================ -->
        <section class="tela" id="tela3">
            <div class="text-center mb-3">
                <div class="mx-auto mb-3" style="width:90px;height:90px;border-radius:50%;background:linear-gradient(135deg,#10b981,#059669);color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 10px 22px rgba(16,185,129,.25)">
                    <i class="bi bi-check-lg fs-1"></i>
                </div>
                <h2 class="fw-bold text-success">Retirada Confirmada!</h2>
                <p class="text-muted">Obrigado! Suas encomendas foram liberadas.</p>
            </div>

            <div class="comprovante mb-4">
                <div class="qr-dummy mb-3" title="QR Code do protocolo (simulado)"></div>
                <div class="text-center mb-3">
                    <div class="text-muted small text-uppercase mb-1">Protocolo de retirada</div>
                    <div class="protocolo" id="vProtocolo">—</div>
                </div>
                <hr class="border-warning opacity-50 my-3">
                <div class="row g-2 mb-2">
                    <div class="col-sm-6">
                        <span class="text-muted small d-block">Morador</span>
                        <strong id="cMorador">—</strong>
                    </div>
                    <div class="col-sm-6">
                        <span class="text-muted small d-block">Unidade</span>
                        <strong id="cUnid">—</strong>
                    </div>
                    <div class="col-sm-6">
                        <span class="text-muted small d-block">Data/Hora</span>
                        <strong id="cData">—</strong>
                    </div>
                    <div class="col-sm-6">
                        <span class="text-muted small d-block">Retirado por</span>
                        <strong id="cAssinatura">—</strong>
                    </div>
                </div>
                <hr class="border-warning opacity-50 my-3">
                <h6 class="fw-bold text-dark mb-2"><i class="bi bi-box-seam me-1"></i> Encomendas retiradas:</h6>
                <div id="cListaEncomendas" class="small mb-0"></div>
                <hr class="border-warning opacity-50 my-3">
                <div class="text-center">
                    <div class="mb-2 text-muted small">Assinatura registrada:</div>
                    <div class="fw-bold fst-italic text-dark fs-5" id="cAssinatura2" style="font-family:cursive;">—</div>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2 justify-content-center">
                <button type="button" onclick="window.print()" class="btn btn-outline-secondary fw-semibold px-4 py-3 rounded-4 btn-full-mobile">
                    <i class="bi bi-printer me-1"></i> IMPRIMIR COMPROVANTE
                </button>
                <button type="button" onclick="window.location.href='retirada_morador.php'" class="btn-principal px-4 py-3 btn-full-mobile">
                    <i class="bi bi-arrow-repeat me-1"></i> NOVA RETIRADA
                </button>
                <button type="button" onclick="window.location.href='index.php'" class="btn btn-light text-secondary fw-semibold px-4 py-3 rounded-4 btn-full-mobile">
                    <i class="bi bi-house-door me-1"></i> Ir ao Painel
                </button>
            </div>
        </section>

    </div>

    <footer class="global-footer text-center py-4 mt-4 text-muted small">
        © <?php echo date('Y'); ?> <strong>PortariaControl</strong> · Desenvolvido por Alexandre Anjos.
    </footer>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
<script>
let MORADOR_CACHE = null;
let ENCOMENDAS_CACHE = [];

function mostrarTela(n) {
    document.querySelectorAll('.tela').forEach(t => t.classList.remove('ativa'));
    document.getElementById('tela'+n).classList.add('ativa');
    for (let i = 1; i <= 3; i++) {
        document.getElementById('barra'+i).classList.toggle('off', i > n);
    }
    document.getElementById('barraBar1').classList.toggle('on', n >= 2);
    document.getElementById('barraBar2').classList.toggle('on', n >= 3);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function sugestao(val) {
    document.getElementById('termo').value = val;
    document.getElementById('termo').focus();
}

function alerta(id, tipo, msg) {
    var html = '';
    if (tipo === 'erro')    html = '<div class="aviso-erro mb-3"><i class="bi bi-exclamation-triangle-fill me-2"></i>'+msg+'</div>';
    if (tipo === 'sucesso') html = '<div class="aviso-sucesso mb-3"><i class="bi bi-check-circle-fill me-2"></i>'+msg+'</div>';
    document.getElementById(id).innerHTML = html;
}

function mascaraCPF(cpf) {
    if (!cpf) return '—';
    cpf = cpf.replace(/\D/g,'');
    if (cpf.length === 11) return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/,'$1.$2.$3-$4');
    return cpf;
}
function mascaraTel(t) {
    if (!t) return '—';
    t = t.replace(/\D/g,'');
    if (t.length === 11) return t.replace(/(\d{2})(\d{5})(\d{4})/,'($1) $2-$3');
    if (t.length === 10) return t.replace(/(\d{2})(\d{4})(\d{4})/,'($1) $2-$3');
    return t;
}

function buscar() {
    const termo = document.getElementById('termo').value.trim();
    if (termo === '') {
        alerta('alerta1','erro','Digite algo para buscar: unidade, CPF, nome ou código de etiqueta.');
        return;
    }
    const btn = document.getElementById('btnBuscar');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-sm"></span>Buscando...';
    alerta('alerta1','sucesso','Processando busca...');

    const fd = new FormData();
    fd.append('acao','buscar');
    fd.append('termo', termo);

    fetch('retirada_processar.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-search me-1"></i> BUSCAR ENCOMENDAS';

            if (!res.ok) {
                alerta('alerta1','erro', res.msg || 'Erro desconhecido.');
                return;
            }
            if (!res.encontrado) {
                alerta('alerta1','erro',
                    'Nenhuma encomenda PENDENTE encontrada com o termo: <strong>'+termo+'</strong>. ' +
                    'Verifique se digitou corretamente ou procure a portaria.');
                return;
            }

            MORADOR_CACHE = res.morador;
            ENCOMENDAS_CACHE = res.encomendas;

            document.getElementById('vMorador').textContent = res.morador.nome_completo;
            document.getElementById('vUnid').textContent = res.morador.numero_unidade;
            document.getElementById('vCpf').textContent = mascaraCPF(res.morador.cpf);
            document.getElementById('vTel').textContent = mascaraTel(res.morador.telefone);
            document.getElementById('vTotal').textContent = res.total;

            // monta cards
            const lista = document.getElementById('listaEncomendas');
            lista.innerHTML = '';
            res.encomendas.forEach((e, idx) => {
                const div = document.createElement('div');
                div.className = 'enc-card selected';
                div.id = 'enc-'+e.encomenda_id;
                div.onclick = () => {
                    const cb = div.querySelector('input[type=checkbox]');
                    cb.checked = !cb.checked;
                    div.classList.toggle('selected', cb.checked);
                };
                div.innerHTML = `
                    <div class="d-flex align-items-center gap-3">
                        <input type="checkbox" class="flex-shrink-0" value="${e.encomenda_id}" checked onchange="document.getElementById('enc-${e.encomenda_id}').classList.toggle('selected', this.checked)">
                        <div class="flex-grow-1">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                <span class="cod-etiqueta">${e.codigo_etiqueta}</span>
                                <span class="tag-origem"><i class="bi bi-truck me-1"></i>${e.transportadora_marketplace}</span>
                            </div>
                            <div class="small text-muted">
                                <i class="bi bi-calendar3 me-1"></i> Recebido em: <strong>${e.data_recebimento}</strong>
                            </div>
                            ${e.observacoes ? `<div class="small mt-1"><i class="bi bi-sticky me-1 text-warning"></i>Obs: ${e.observacoes}</div>` : ''}
                        </div>
                        <i class="bi bi-check2-square fs-2" style="color:var(--cor-principal)"></i>
                    </div>`;
                lista.appendChild(div);
            });

            alerta('alerta2','sucesso',
                'Encontrado <strong>'+res.total+'</strong> encomenda(s) pendente(s). ' +
                'Marque as que deseja retirar, digite seu nome e confirme!');
            mostrarTela(2);
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-search me-1"></i> BUSCAR ENCOMENDAS';
            alerta('alerta1','erro','Falha de rede: '+ err.message);
        });
}

function confirmarRetirada() {
    const cbs = document.querySelectorAll('#listaEncomendas input[type=checkbox]:checked');
    const ids = Array.from(cbs).map(c => c.value);
    const assinatura = document.getElementById('assinatura').value.trim();
    const porteiro = document.getElementById('porteiro').value.trim();

    if (ids.length === 0) {
        alerta('alerta2','erro','Selecione PELO MENOS UMA encomenda para retirar.');
        return;
    }
    if (assinatura === '') {
        alerta('alerta2','erro','Digite seu nome completo para assinar a retirada.');
        return;
    }

    const btn = document.getElementById('btnConfirmar');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-sm"></span>Confirmando...';

    const fd = new FormData();
    fd.append('acao','confirmar');
    ids.forEach(i => fd.append('ids[]', i));
    fd.append('assinatura', assinatura);
    fd.append('porteiro', porteiro || 'Autoatendimento (Morador)');

    fetch('retirada_processar.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (!res.ok) {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> CONFIRMAR RETIRADA';
                alerta('alerta2','erro', res.msg || 'Erro ao confirmar.');
                return;
            }

            const c = res.comprovante;
            document.getElementById('vProtocolo').textContent = c.protocolo;
            document.getElementById('cMorador').textContent = c.morador;
            document.getElementById('cUnid').textContent = c.unidade;
            document.getElementById('cData').textContent = c.data;
            document.getElementById('cAssinatura').textContent = c.assinatura;
            document.getElementById('cAssinatura2').textContent = c.assinatura;

            const listaC = document.getElementById('cListaEncomendas');
            listaC.innerHTML = '';
            c.encomendas.forEach(e => {
                const row = document.createElement('div');
                row.className = 'd-flex justify-content-between border-bottom border-warning border-opacity-25 py-2';
                row.innerHTML = `
                    <div><span class="cod-etiqueta me-2">${e.codigo}</span>
                    <span class="text-muted">${e.origem}</span></div>
                    <span class="text-success fw-bold"><i class="bi bi-check-lg me-1"></i>Retirada</span>`;
                listaC.appendChild(row);
            });

            mostrarTela(3);
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> CONFIRMAR RETIRADA';
            alerta('alerta2','erro','Falha de rede: '+ err.message);
        });
}

// Bind enter no input
document.getElementById('termo').addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); buscar(); }
});
document.getElementById('assinatura').addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); confirmarRetirada(); }
});
</script>
</body>
</html>