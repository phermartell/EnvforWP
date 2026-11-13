jQuery(document).ready(function ($) {
    // ---------- Repeater de trabajos realizados ----------
    var worksContainer = $('#envfor-project-works');
    var worksHidden = $('#envfor_project_works');

    var works = [];
    try {
        var rawWorks = worksContainer.attr('data-works');
        if (rawWorks) {
            works = JSON.parse(rawWorks);
        }
    } catch (e) {
        works = [];
    }
    if (!Array.isArray(works)) {
        works = [];
    }

    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function renderWorks() {
        worksContainer.empty();
        works.forEach(function (work, i) {
            worksContainer.append(
                '<div class="envfor-work" style="display:flex; gap:8px; margin-bottom:6px;">' +
                '<input type="text" class="envfor-work-name" data-i="' + i + '" value="' + esc(work) + '" style="flex:1;">' +
                '<button type="button" class="button envfor-work-up" data-i="' + i + '" title="Subir">↑</button>' +
                '<button type="button" class="button envfor-work-down" data-i="' + i + '" title="Bajar">↓</button>' +
                '<button type="button" class="button envfor-work-remove" data-i="' + i + '" title="Eliminar">×</button>' +
                '</div>'
            );
        });
    }

    function bindWorks() {
        worksContainer.find('.envfor-work-name').off('input').on('input', function () {
            works[+$(this).data('i')] = $(this).val();
        });

        worksContainer.find('.envfor-work-remove').off('click').on('click', function () {
            works.splice(+$(this).data('i'), 1);
            renderWorks();
            bindWorks();
        });

        worksContainer.find('.envfor-work-up').off('click').on('click', function () {
            var i = +$(this).data('i');
            if (i > 0) {
                var t = works[i - 1];
                works[i - 1] = works[i];
                works[i] = t;
                renderWorks();
                bindWorks();
            }
        });

        worksContainer.find('.envfor-work-down').off('click').on('click', function () {
            var i = +$(this).data('i');
            if (i < works.length - 1) {
                var t = works[i + 1];
                works[i + 1] = works[i];
                works[i] = t;
                renderWorks();
                bindWorks();
            }
        });
    }

    $('#envfor-add-work').off('click').on('click', function () {
        works.push('');
        renderWorks();
        bindWorks();
    });

    renderWorks();
    bindWorks();

    // ---------- Galería ----------
    var galleryContainer = $('#envfor-project-gallery');
    var galleryHidden = $('#envfor_project_gallery');

    var gallery = [];
    try {
        var rawGallery = galleryContainer.attr('data-gallery');
        if (rawGallery) {
            gallery = JSON.parse(rawGallery);
        }
    } catch (e) {
        gallery = [];
    }
    if (!Array.isArray(gallery)) {
        gallery = [];
    }

    function renderGallery() {
        galleryContainer.empty();
        gallery.forEach(function (item, i) {
            var url = (item && item.url) ? item.url : '';
            var caption = (item && item.caption) ? item.caption : '';
            galleryContainer.append(
                '<div class="envfor-gallery-item" style="display:flex; gap:8px; align-items:flex-start; border:1px solid #ccd0d4; padding:8px; margin-bottom:8px;">' +
                '<img src="' + esc(url) + '" style="width:80px; height:80px; object-fit:cover; border:1px solid #ccc;">' +
                '<div style="flex:1;">' +
                '<label>Caption<br>' +
                '<input type="text" class="envfor-gallery-caption" data-i="' + i + '" value="' + esc(caption) + '" style="width:100%;"></label>' +
                '</div>' +
                '<button type="button" class="button envfor-gallery-up" data-i="' + i + '" title="Subir">↑</button>' +
                '<button type="button" class="button envfor-gallery-down" data-i="' + i + '" title="Bajar">↓</button>' +
                '<button type="button" class="button envfor-gallery-remove" data-i="' + i + '" title="Eliminar">×</button>' +
                '</div>'
            );
        });
    }

    function bindGallery() {
        galleryContainer.find('.envfor-gallery-caption').off('input').on('input', function () {
            gallery[+$(this).data('i')].caption = $(this).val();
        });

        galleryContainer.find('.envfor-gallery-remove').off('click').on('click', function () {
            gallery.splice(+$(this).data('i'), 1);
            renderGallery();
            bindGallery();
        });

        galleryContainer.find('.envfor-gallery-up').off('click').on('click', function () {
            var i = +$(this).data('i');
            if (i > 0) {
                var t = gallery[i - 1];
                gallery[i - 1] = gallery[i];
                gallery[i] = t;
                renderGallery();
                bindGallery();
            }
        });

        galleryContainer.find('.envfor-gallery-down').off('click').on('click', function () {
            var i = +$(this).data('i');
            if (i < gallery.length - 1) {
                var t = gallery[i + 1];
                gallery[i + 1] = gallery[i];
                gallery[i] = t;
                renderGallery();
                bindGallery();
            }
        });
    }

    renderGallery();
    bindGallery();

    var galleryUploader;

    $('#envfor-add-gallery').off('click').on('click', function (e) {
        e.preventDefault();

        if (galleryUploader) {
            galleryUploader.open();
            return;
        }

        galleryUploader = wp.media({
            title: 'Seleccionar imágenes de galería',
            button: { text: 'Usar estas imágenes' },
            multiple: true,
            library: { type: 'image' }
        });

        galleryUploader.on('select', function () {
            var selection = galleryUploader.state().get('selection');
            selection.each(function (attachment) {
                var att = attachment.toJSON();
                gallery.push({ id: att.id, caption: '', url: att.url });
            });
            renderGallery();
            bindGallery();
        });

        galleryUploader.open();
    });

    // ---------- Serializar works y galería al guardar ----------
    $('#post').on('submit', function () {
        worksHidden.val(JSON.stringify(works));

        var cleanGallery = gallery.map(function (g) {
            return { id: g.id, caption: g.caption };
        });
        galleryHidden.val(JSON.stringify(cleanGallery));
    });


    // ---------- Selector de imagen hero ----------
    var heroMediaUploader;

    $('#envfor_project_upload_hero_image_btn').on('click', function (e) {
        e.preventDefault();

        if (heroMediaUploader) {
            heroMediaUploader.open();
            return;
        }

        heroMediaUploader = wp.media({
            title: 'Seleccionar imagen hero',
            button: { text: 'Usar esta imagen' },
            multiple: false,
            library: { type: 'image' }
        });

        heroMediaUploader.on('select', function () {
            var attachment = heroMediaUploader.state().get('selection').first().toJSON();
            $('#envfor_project_hero_image_id').val(attachment.id);
            $('#envfor_project_hero_image_preview').html('<img src="' + attachment.url + '" style="max-width:100%; height:auto; border:1px solid #ccc;">');
            $('#envfor_project_remove_hero_image_btn').show();
        });

        heroMediaUploader.open();
    });

    $('#envfor_project_remove_hero_image_btn').on('click', function () {
        $('#envfor_project_hero_image_id').val('');
        $('#envfor_project_hero_image_preview').html('');
        $(this).hide();
    });

    // ---------- Selector de imagen Home ----------
    var homeMediaUploader;

    $('#envfor_project_upload_home_image_btn').on('click', function (e) {
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
            $('#envfor_project_home_image_id').val(attachment.id);
            $('#envfor_project_home_image_preview').html('<img src="' + attachment.url + '" style="max-width:100%; height:auto; border:1px solid #ccc;">');
            $('#envfor_project_remove_home_image_btn').show();
        });

        homeMediaUploader.open();
    });

    $('#envfor_project_remove_home_image_btn').on('click', function () {
        $('#envfor_project_home_image_id').val('');
        $('#envfor_project_home_image_preview').html('');
        $(this).hide();
    });
});

