jQuery(document).ready(function($){
    var mediaUploader;

    $('#envfor_upload_image_btn').click(function(e) {
        e.preventDefault();
        
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        
        mediaUploader = wp.media.frames.file_frame = wp.media({
            title: 'Seleccionar Imagen de Fondo',
            button: {
                text: 'Usar esta imagen'
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });
        
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#envfor_image_id').val(attachment.id);
            $('#envfor_image_preview').html('<img src="' + attachment.url + '" style="max-width: 100%; height: auto; border: 1px solid #ccc; margin-top: 10px;">');
            $('#envfor_remove_image_btn').show();
        });
        
        mediaUploader.open();
    });

    $('#envfor_remove_image_btn').click(function(e) {
        e.preventDefault();
        $('#envfor_image_id').val('');
        $('#envfor_image_preview').html('');
        $(this).hide();
    });
});
