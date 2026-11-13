jQuery(document).ready(function ($) {
    var container = $('#envfor-testimonials-list');
    var hiddenInput = $('#envfor_testimonials_json');
    var addBtn = $('#envfor-add-testimonial-btn');
    var form = $('#envfor-testimonials-form');

    var testimonials = [];
    try {
        var raw = container.attr('data-testimonials');
        if (raw) {
            testimonials = JSON.parse(raw);
        }
    } catch {
        testimonials = [];
    }
    if (!Array.isArray(testimonials)) {
        testimonials = [];
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

        if (testimonials.length === 0) {
            container.append(
                '<div style="padding: 24px; background: #fff; border: 1px dashed #c3c4c7; text-align: center; color: #646970;">' +
                'No hay testimoniales registrados. Haz clic en "+ Añadir testimonio" para crear uno.' +
                '</div>'
            );
            return;
        }

        testimonials.forEach(function (item, index) {
            var isVisible = !!item.visible;
            var isFirst = index === 0;
            var isLast = index === testimonials.length - 1;

            var card = $('<div class="envfor-testimonial-card" data-index="' + index + '" style="background:#fff; border:1px solid #c3c4c7; border-radius:4px; padding:18px 20px; margin-bottom:16px; box-shadow:0 1px 1px rgba(0,0,0,0.04);"></div>');

            // Header con orden, título rápido, acciones subir/bajar/eliminar
            var header = $('<div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #f0f0f1; padding-bottom:12px; margin-bottom:14px;"></div>');
            var titleText = item.company ? esc(item.company) : 'Testimonio #' + (index + 1);
            var titleLeft = $('<div style="display:flex; align-items:center; gap:10px;"></div>');
            titleLeft.append('<strong style="font-size:14px; color:#1d2327;">' + (index + 1) + '. ' + titleText + '</strong>');

            var visibilityLabel = $('<label style="display:flex; align-items:center; gap:6px; font-size:13px; cursor:pointer; margin-left:14px; color:' + (isVisible ? '#1d2327' : '#8c8f94') + ';"></label>');
            var chk = $('<input type="checkbox" class="envfor-toggle-visible" data-index="' + index + '"' + (isVisible ? ' checked' : '') + '>');
            visibilityLabel.append(chk);
            visibilityLabel.append('<span>' + (isVisible ? 'Visible en el Home' : 'Oculto') + '</span>');
            titleLeft.append(visibilityLabel);

            header.append(titleLeft);

            var actions = $('<div style="display:flex; gap:6px;"></div>');
            actions.append('<button type="button" class="button button-small envfor-move-up" data-index="' + index + '"' + (isFirst ? ' disabled' : '') + ' title="Subir">↑ Subir</button>');
            actions.append('<button type="button" class="button button-small envfor-move-down" data-index="' + index + '"' + (isLast ? ' disabled' : '') + ' title="Bajar">↓ Bajar</button>');
            actions.append('<button type="button" class="button button-small button-link-delete envfor-delete-card" data-index="' + index + '" style="margin-left:8px;" title="Eliminar">Eliminar</button>');
            header.append(actions);

            card.append(header);

            // Cuerpo del testimonio
            var body = $('<div style="display:grid; grid-template-columns: 1fr 280px; gap:20px;"></div>');

            // Columna izquierda: Empresa y Cita
            var textCol = $('<div></div>');

            textCol.append(
                '<p style="margin-top:0; margin-bottom:12px;">' +
                '<label><strong>Empresa *:</strong><br>' +
                '<input type="text" class="regular-text envfor-input-company" data-index="' + index + '" value="' + esc(item.company) + '" style="width:100%; max-width:400px;" placeholder="Ej. CIGSA">' +
                '</label>' +
                '</p>'
            );

            textCol.append(
                '<p style="margin-bottom:0;">' +
                '<label><strong>Cita / Testimonio *:</strong><br>' +
                '<textarea class="large-text envfor-input-quote" data-index="' + index + '" rows="4" style="width:100%;" placeholder="Texto del testimonio...">' + esc(item.quote) + '</textarea>' +
                '</label>' +
                '</p>'
            );

            body.append(textCol);

            // Columna derecha: Imagen y ALT
            var imgCol = $('<div style="background:#f6f7f7; padding:12px; border:1px solid #dcdcde; border-radius:3px;"></div>');
            imgCol.append('<strong style="display:block; margin-bottom:8px; font-size:12px;">Fotografía / Imagen:</strong>');

            var previewSrc = item.image_preview || item.image_url || '';
            var previewHtml = previewSrc
                ? '<img src="' + esc(previewSrc) + '" style="width:100%; height:120px; object-fit:cover; display:block; border:1px solid #ccd0d4; border-radius:2px; margin-bottom:8px;">'
                : '<div style="width:100%; height:120px; background:#e0e0e0; display:flex; align-items:center; justify-content:center; color:#666; font-size:12px; margin-bottom:8px; border:1px dashed #ccc;">(Sin imagen)</div>';

            imgCol.append('<div class="envfor-img-preview-box">' + previewHtml + '</div>');

            var altText = item.image_alt ? esc(item.image_alt) : '<em>(Sin texto alternativo)</em>';
            imgCol.append('<div class="envfor-img-alt-box" style="font-size:11px; color:#555; margin-bottom:10px;"><strong>ALT:</strong> <span>' + altText + '</span></div>');

            var btnRow = $('<div style="display:flex; gap:6px;"></div>');
            btnRow.append('<button type="button" class="button button-small envfor-select-image-btn" data-index="' + index + '">Elegir imagen</button>');
            btnRow.append('<button type="button" class="button button-small envfor-remove-image-btn" data-index="' + index + '"' + (!previewSrc ? ' style="display:none;"' : '') + '>Quitar</button>');
            imgCol.append(btnRow);

            body.append(imgCol);
            card.append(body);

            container.append(card);
        });
    }

    function bind() {
        container.find('.envfor-input-company').off('input').on('input', function () {
            var i = +$(this).data('index');
            testimonials[i].company = $(this).val();
        });

        container.find('.envfor-input-quote').off('input').on('input', function () {
            var i = +$(this).data('index');
            testimonials[i].quote = $(this).val();
        });

        container.find('.envfor-toggle-visible').off('change').on('change', function () {
            var i = +$(this).data('index');
            testimonials[i].visible = $(this).is(':checked');
            render();
            bind();
        });

        container.find('.envfor-move-up').off('click').on('click', function () {
            var i = +$(this).data('index');
            if (i > 0) {
                var temp = testimonials[i - 1];
                testimonials[i - 1] = testimonials[i];
                testimonials[i] = temp;
                render();
                bind();
            }
        });

        container.find('.envfor-move-down').off('click').on('click', function () {
            var i = +$(this).data('index');
            if (i < testimonials.length - 1) {
                var temp = testimonials[i + 1];
                testimonials[i + 1] = testimonials[i];
                testimonials[i] = temp;
                render();
                bind();
            }
        });

        container.find('.envfor-delete-card').off('click').on('click', function () {
            var i = +$(this).data('index');
            testimonials.splice(i, 1);
            render();
            bind();
        });

        // Selector de Biblioteca de medios
        container.find('.envfor-select-image-btn').off('click').on('click', function (e) {
            e.preventDefault();
            var i = +$(this).data('index');

            var uploader = wp.media({
                title: 'Seleccionar imagen para testimonio',
                button: { text: 'Usar esta imagen' },
                multiple: false,
                library: { type: 'image' }
            });

            uploader.on('select', function () {
                var attachment = uploader.state().get('selection').first().toJSON();
                var altVal = attachment.alt ? attachment.alt : '';
                var displayUrl = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;

                testimonials[i].image_id = attachment.id;
                testimonials[i].image_url = attachment.url;
                testimonials[i].image_preview = displayUrl;
                // ALT propio del adjunto seleccionado. Nunca hereda el de la foto previa.
                testimonials[i].image_alt = altVal;

                render();
                bind();
            });

            uploader.open();
        });

        container.find('.envfor-remove-image-btn').off('click').on('click', function (e) {
            e.preventDefault();
            var i = +$(this).data('index');
            testimonials[i].image_id = 0;
            testimonials[i].image_url = '';
            testimonials[i].image_preview = '';
            testimonials[i].image_alt = '';
            render();
            bind();
        });
    }

    addBtn.on('click', function (e) {
        e.preventDefault();
        testimonials.push({
            id: 'testimonio_' + Date.now(),
            company: '',
            quote: '',
            image_id: 0,
            image_url: '',
            image_preview: '',
            image_alt: '',
            visible: true
        });
        render();
        bind();
    });

    // Serializar JSON al enviar formulario
    form.on('submit', function () {
        hiddenInput.val(JSON.stringify(testimonials));
    });

    render();
    bind();
});
