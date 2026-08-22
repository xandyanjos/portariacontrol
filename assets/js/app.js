(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        inicializarMenuHamburguer();
        inicializarTabelaResponsiva();
        inicializarFecharSidebarAoClicarFora();
    });

    function inicializarMenuHamburguer() {
        const btnHamburguer = document.getElementById('btnHamburguer');
        const sidebar = document.getElementById('sidebarPrincipal');
        const overlay = document.getElementById('overlayMobile');

        if (!btnHamburguer || !sidebar) return;

        function toggleMenu() {
            sidebar.classList.toggle('aberta');
            if (overlay) overlay.classList.toggle('visivel');
            document.body.classList.toggle('menu-aberto');
        }

        btnHamburguer.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleMenu();
        });

        const btnFecharSidebar = document.getElementById('btnFecharSidebar');
        if (btnFecharSidebar) {
            btnFecharSidebar.addEventListener('click', function(e) {
                e.stopPropagation();
                fecharMenu();
            });
        }

        window._fecharMenuSidebar = fecharMenu;

        function fecharMenu() {
            sidebar.classList.remove('aberta');
            if (overlay) overlay.classList.remove('visivel');
            document.body.classList.remove('menu-aberto');
        }

        const linksSidebar = sidebar.querySelectorAll('.nav-sidebar a');
        linksSidebar.forEach(function(link) {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 992) {
                    setTimeout(fecharMenu, 150);
                }
            });
        });

        window.addEventListener('resize', function() {
            if (window.innerWidth > 992) {
                fecharMenu();
            }
        });
    }

    function inicializarFecharSidebarAoClicarFora() {
        const overlay = document.getElementById('overlayMobile');
        const sidebar = document.getElementById('sidebarPrincipal');
        const btnHamburguer = document.getElementById('btnHamburguer');

        if (!overlay) return;

        overlay.addEventListener('click', function() {
            if (window._fecharMenuSidebar) {
                window._fecharMenuSidebar();
            }
        });

        document.addEventListener('click', function(e) {
            if (window.innerWidth > 992) return;
            if (!sidebar || !sidebar.classList.contains('aberta')) return;
            if (btnHamburguer && btnHamburguer.contains(e.target)) return;
            if (sidebar.contains(e.target)) return;
            if (overlay && !overlay.contains(e.target)) return;

            if (window._fecharMenuSidebar) {
                window._fecharMenuSidebar();
            }
        });
    }

    function inicializarTabelaResponsiva() {
        const tabelas = document.querySelectorAll('.table-responsive-stack');
        tabelas.forEach(function(tabela) {
            const headers = [];
            const ths = tabela.querySelectorAll('thead th');
            ths.forEach(function(th) {
                headers.push(th.textContent.trim());
            });

            const rows = tabela.querySelectorAll('tbody tr');
            rows.forEach(function(row) {
                const tds = row.querySelectorAll('td');
                tds.forEach(function(td, index) {
                    if (!td.hasAttribute('data-label') && headers[index]) {
                        td.setAttribute('data-label', headers[index]);
                    }
                    if (td.classList.contains('text-end') || index === tds.length - 1) {
                        td.classList.add('td-actions');
                    }
                });
            });
        });
    }
})();
