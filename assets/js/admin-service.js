jQuery(document).ready(function ($) {
    // ---------- Repeater de grupos ----------
    var container = $('#envfor-service-groups');
    var hidden = $('#envfor_service_groups');

    var groups = [];
    try {
        var raw = container.attr('data-groups');
        if (raw) {
            groups = JSON.parse(raw);
        }
    } catch {
        groups = [];
    }
    if (!Array.isArray(groups)) {
        groups = [];
    }

    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function render() {
        container.empty();

        groups.forEach(function (group, gi) {
            var box = $('<div class="envfor-group" style="border:1px solid #ccd0d4; padding:10px; margin-bottom:12px;"></div>');

            var head = $('<div style="display:flex; gap:8px; align-items:center; margin-bottom:8px;"></div>');
            head.append(
                '<label style="flex:1;">Grupo<br>' +
                '<input type="text" class="envfor-group-name" data-gi="' + gi + '" value="' + esc(group.name) + '" style="width:100%;"></label>'
            );
            head.append('<button type="button" class="button envfor-group-up" data-gi="' + gi + '" title="Subir grupo">↑</button>');
            head.append('<button type="button" class="button envfor-group-down" data-gi="' + gi + '" title="Bajar grupo">↓</button>');
            head.append('<button type="button" class="button envfor-group-remove" data-gi="' + gi + '">Eliminar grupo</button>');
            box.append(head);

            var itemsBox = $('<div class="envfor-items" style="padding-left:10px;"></div>');
            (group.items || []).forEach(function (item, ii) {
                itemsBox.append(
                    '<div class="envfor-item" style="display:flex; gap:8px; margin-bottom:6px;">' +
                    '<input type="text" class="envfor-item-name" data-gi="' + gi + '" data-ii="' + ii + '" value="' + esc(item) + '" style="flex:1;">' +
                    '<button type="button" class="button envfor-item-up" data-gi="' + gi + '" data-ii="' + ii + '" title="Subir servicio">↑</button>' +
                    '<button type="button" class="button envfor-item-down" data-gi="' + gi + '" data-ii="' + ii + '" title="Bajar servicio">↓</button>' +
                    '<button type="button" class="button envfor-item-remove" data-gi="' + gi + '" data-ii="' + ii + '" title="Eliminar servicio">×</button>' +
                    '</div>'
                );
            });
            box.append(itemsBox);

            box.append('<button type="button" class="button envfor-add-item" data-gi="' + gi + '">Agregar servicio</button>');

            container.append(box);
        });
    }

    function bind() {
        container.find('.envfor-group-name').off('input').on('input', function () {
            groups[+$(this).data('gi')].name = $(this).val();
        });

        container.find('.envfor-item-name').off('input').on('input', function () {
            groups[+$(this).data('gi')].items[+$(this).data('ii')] = $(this).val();
        });

        container.find('.envfor-add-item').off('click').on('click', function () {
            groups[+$(this).data('gi')].items.push('');
            render();
            bind();
        });

        container.find('.envfor-item-remove').off('click').on('click', function () {
            var gi = +$(this).data('gi');
            var ii = +$(this).data('ii');
            groups[gi].items.splice(ii, 1);
            render();
            bind();
        });

        container.find('.envfor-item-up').off('click').on('click', function () {
            var gi = +$(this).data('gi');
            var ii = +$(this).data('ii');
            var items = groups[gi].items;
            if (ii > 0) {
                var t = items[ii - 1];
                items[ii - 1] = items[ii];
                items[ii] = t;
                render();
                bind();
            }
        });

        container.find('.envfor-item-down').off('click').on('click', function () {
            var gi = +$(this).data('gi');
            var ii = +$(this).data('ii');
            var items = groups[gi].items;
            if (ii < items.length - 1) {
                var t = items[ii + 1];
                items[ii + 1] = items[ii];
                items[ii] = t;
                render();
                bind();
            }
        });

        container.find('.envfor-group-remove').off('click').on('click', function () {
            groups.splice(+$(this).data('gi'), 1);
            render();
            bind();
        });

        container.find('.envfor-group-up').off('click').on('click', function () {
            var gi = +$(this).data('gi');
            if (gi > 0) {
                var t = groups[gi - 1];
                groups[gi - 1] = groups[gi];
                groups[gi] = t;
                render();
                bind();
            }
        });

        container.find('.envfor-group-down').off('click').on('click', function () {
            var gi = +$(this).data('gi');
            if (gi < groups.length - 1) {
                var t = groups[gi + 1];
                groups[gi + 1] = groups[gi];
                groups[gi] = t;
                render();
                bind();
            }
        });
    }

    $('#envfor-add-group').off('click').on('click', function () {
        groups.push({ name: '', items: [] });
        render();
        bind();
    });

    $('#post').on('submit', function () {
        hidden.val(JSON.stringify(groups));
    });

    render();
    bind();

    // ---------- Selector de imagen ----------
    var mediaUploader;

    $('#envfor_service_upload_image_btn').on('click', function (e) {
        e.preventDefault();

        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        mediaUploader = wp.media({
            title: 'Seleccionar imagen del servicio',
            button: { text: 'Usar esta imagen' },
            multiple: false,
            library: { type: 'image' }
        });

        mediaUploader.on('select', function () {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#envfor_service_image_id').val(attachment.id);
            $('#envfor_service_image_preview').html('<img src="' + attachment.url + '" style="max-width:100%; height:auto; border:1px solid #ccc;">');
            $('#envfor_service_remove_image_btn').show();
        });

        mediaUploader.open();
    });

    $('#envfor_service_remove_image_btn').on('click', function () {
        $('#envfor_service_image_id').val('');
        $('#envfor_service_image_preview').html('');
        $(this).hide();
    });

    // ---------- Selector de imagen Home ----------
    var homeMediaUploader;

    $('#envfor_service_upload_home_image_btn').on('click', function (e) {
        e.preventDefault();

        if (homeMediaUploader) {
            homeMediaUploader.open();
            return;
        }

        homeMediaUploader = wp.media({
            title: 'Seleccionar imagen del Home',
            button: { text: 'Usar esta imagen' },
            multiple: false,
            library: { type: 'image' }
        });

        homeMediaUploader.on('select', function () {
            var attachment = homeMediaUploader.state().get('selection').first().toJSON();
            $('#envfor_service_home_image_id').val(attachment.id);
            $('#envfor_service_home_image_preview').html('<img src="' + attachment.url + '" style="max-width:100%; height:auto; border:1px solid #ccc;">');
            $('#envfor_service_remove_home_image_btn').show();
        });

        homeMediaUploader.open();
    });

    $('#envfor_service_remove_home_image_btn').on('click', function () {
        $('#envfor_service_home_image_id').val('');
        $('#envfor_service_home_image_preview').html('');
        $(this).hide();
    });
});
