jQuery(document).ready(function ($) {
    function setupImagePicker(btnId, removeBtnId, inputId, previewId, altTextId, defaultAlt) {
        var uploader;

        $(btnId).on('click', function (e) {
            e.preventDefault();

            if (uploader) {
                uploader.open();
                return;
            }

            uploader = wp.media({
                title: 'Seleccionar imagen',
                button: { text: 'Usar esta imagen' },
                multiple: false,
                library: { type: 'image' }
            });

            uploader.on('select', function () {
                var attachment = uploader.state().get('selection').first().toJSON();
                $(inputId).val(attachment.id);

                var displayUrl = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
                $(previewId).html('<img src="' + displayUrl + '" style="max-width:280px; height:auto; display:block; border:1px solid #ccd0d4; border-radius:2px;">');

                var alt = attachment.alt ? attachment.alt : '';
                if (alt !== '') {
                    $(altTextId).text(alt);
                } else {
                    $(altTextId).html('<em>(Sin texto alternativo configurado en medios)</em>');
                }

                $(removeBtnId).show();
            });

            uploader.open();
        });

        $(removeBtnId).on('click', function (e) {
            e.preventDefault();
            $(inputId).val('');
            $(previewId).html('<p style="font-style:italic; color:#666; margin:4px 0;">(Actualmente usando imagen predeterminada del sitio)</p>');
            $(altTextId).text(defaultAlt);
            $(this).hide();
        });
    }

    var defaultAlt1 = 'Técnicos con cascos de seguridad y chalecos reflejantes sembrando un brote de árbol en terreno con vegetación';
    var defaultAlt2 = 'Técnicos de campo con cascos naranjas y chalecos reflejantes realizando labores en zona con cactáceas y matorrales';

    setupImagePicker(
        '#envfor_about_image1_btn',
        '#envfor_about_image1_remove',
        '#envfor_about_image1_id',
        '#envfor_about_image1_preview',
        '#envfor_about_image1_alt_text',
        defaultAlt1
    );

    setupImagePicker(
        '#envfor_about_image2_btn',
        '#envfor_about_image2_remove',
        '#envfor_about_image2_id',
        '#envfor_about_image2_preview',
        '#envfor_about_image2_alt_text',
        defaultAlt2
    );
});
