jQuery(document).ready(function ($) {
    'use strict';
    'esversion: 6';

    /**
     * Ajax загрузка изображения товара в админке
     *
     * @source https://dev.to/kelin1003/utilising-wordpress-media-library-for-uploading-files-2b01
     */
    $(document).on('click', 'input#upload_image_button', function (event) {
        event.preventDefault();

        // Объект библиотеки изображений
        const customMediaLibrary = window.wp.media({
            frame: 'select',
            title: 'Выберите изображение',
            multiple: false,
            library: {
                order: 'DESC',
                orderby: 'date',
                type: 'image',
                search: null,
                uploadedTo: null
            },
            button: {
                text: 'Готово'
            }
        });

        // Открыть библиотеку изображений
        customMediaLibrary.open();

        // Действие по выбору изображения
        customMediaLibrary.on('select', function () {
            let selectedImages = customMediaLibrary.state().get('selection');
            $.ajax({
                url: img_ajax_obj.ajaxurl,
                data: {
                    'action': 'img_ajax_obj_request',
                    'type': 'POST',
                    'content': JSON.stringify(selectedImages),
                    'security': img_ajax_obj.nonce
                }
            })
            .done(function (data) {
                let $result = JSON.parse(data);
                if ($result.status == false) {
                    console.log($result.content);
                } else {
                    $('input#upload_image_button').val('Изменить картинку');
                    $('input#upload_image_button').after('<p style="margin-bottom:3px;"><strong>Картинка успешно добавлена. Перезагрузите страницу, чтобы ее увидеть.</strong></p>');
                }
            })
            .fail(function (errorThrown) {
                console.log(errorThrown);
            });

        });
    });
});