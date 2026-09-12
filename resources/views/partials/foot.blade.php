<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>

<script src="{{ asset('assets/plugins/simplebar/js/simplebar.min.js') }}"></script>
<script src="{{ asset('assets/plugins/metismenu/js/metisMenu.min.js') }}"></script>
<script src="{{ asset('assets/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
<script src="{{ asset('assets/plugins/apexcharts-bundle/js/apexcharts.min.js') }}"></script>
<script src="{{ asset('assets/js/widgets.js') }}"></script>

<script src="{{ asset('assets/plugins/Drag-And-Drop/dist/imageuploadify.min.js') }}"></script>
<script src="{{ asset('assets/js/app.js') }}"></script>
<script src="{{ asset('assets/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/plugins/datatable/js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script>
<script src="{{ asset('assets/js/pace.min.js') }}"></script>

<script>
    // Rendre TOUS les tableaux DataTables lisibles sur mobile : défilement
    // horizontal (toutes les colonnes + boutons restent accessibles au doigt)
    // + libellés FR. (scrollX est fiable avec DataTables 1.10, contrairement à
    // l'extension Responsive qui exige une version plus récente.)
    if ($.fn.dataTable) {
        $.extend(true, $.fn.dataTable.defaults, {
            scrollX: true,
            autoWidth: false,
            language: {
                search: 'Rechercher :',
                lengthMenu: 'Afficher _MENU_ éléments',
                info: '_START_ à _END_ sur _TOTAL_',
                infoEmpty: '0 élément',
                infoFiltered: '(filtré sur _MAX_)',
                zeroRecords: 'Aucun résultat',
                emptyTable: 'Aucune donnée disponible',
                paginate: { first: '«', previous: '‹', next: '›', last: '»' },
            },
        });
    }

    // Tableaux « simples » (non DataTables : pages détail/rapport) : on les rend
    // scrollables horizontalement sur mobile en les enveloppant dans .table-responsive.
    window.addEventListener('load', function () {
        document.querySelectorAll('table.table').forEach(function (t) {
            if (t.closest('.table-responsive') || t.closest('.dataTables_wrapper')) return;
            const wrap = document.createElement('div');
            wrap.className = 'table-responsive';
            t.parentNode.insertBefore(wrap, t);
            wrap.appendChild(t);
        });
    });
</script>

<script>
    $(function () {
        $('.single-select').each(function () {
            const modal = $(this).closest('.modal');
            $(this).select2({
                theme: 'bootstrap4',
                allowClear: false,
                dropdownParent: modal.length ? modal : $(document.body),
            });
        });
    });
</script>
