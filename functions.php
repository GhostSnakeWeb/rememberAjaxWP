<?php
if (!defined('_S_VERSION')) {
    define('_S_VERSION', '1.0.0');
}

/**
 * Подключение стилей
 *
 * @testedwith    WooCommerce 6.2.1
 * @verphp        7.4
 *
 */
function plugStyles()
{
    wp_enqueue_style('style', get_stylesheet_uri());
}
add_action('wp_enqueue_scripts', 'plugStyles');

/**
 * Подключение скриптов
 *
 * @testedwith    WooCommerce 6.2.1
 * @verphp        7.4
 *
 */
function plugScripts()
{
    /* jQuery */
    wp_deregister_script('jquery-core');
    wp_deregister_script('jquery');
    wp_register_script('jquery-core', get_template_directory_uri() . '/assets/js/jquery-3.6.0.min.js', [], false, true);
    wp_register_script('jquery', false, ['jquery-core'], false, true);
    wp_enqueue_script('jquery');

    // Main.js
    wp_enqueue_script('main', get_template_directory_uri() . "/assets/js/main.js", ['jquery'], '1.0', true);

    // Передача переменных в JS
    wp_localize_script(
        'main',
        'img_ajax_obj',
        [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('img-nonce')
        ]
    );
}
add_action('admin_footer', 'plugScripts');

/**
 * Произвольные поля для товара Woocommerce в вкладке "Дополнительно"
 *
 * @testedwith    WooCommerce 6.2.1
 * @verphp        7.4
 *
 */
function addCustomFields()
{
    global $product, $post;

    // Группировка полей
    echo '<div class="options_group">';

    // Выбор типа продукта
    woocommerce_wp_select(
        [
            'id' => '_select_type_product',
            'label' => 'Тип продукта',
            'options' => [
                'rare' => __('Rare', 'woocommerce'),
                'frequent' => __('Frequent', 'woocommerce'),
                'unusual' => __('Unusual', 'woocommerce'),
            ],
            'value' => get_post_meta($post->ID, 'select_type_product', true)
        ]
    );
    ?>

    <p class="form-field custom_field_type">
        <label for="createDate">Когда создан продукт:</label>
        <span class="wrap">
            <input
                    id="createDate"
                    type="date"
                    name="_create_date"
                    value="<?php echo get_post_meta($post->ID, 'create_date', true); ?>"
                    style="width: 15.75%;margin-right: 2%;"/>
        </span>
    </p>

    <p class="form-field custom_field_type img_container">
        <label>Картинка для товара:</label>
        <?php $imgSrc = wp_get_attachment_image_url(get_post_meta($post->ID, 'photo', true));
            if(isset($imgSrc)) { ?>
                <img src="<?=$imgSrc;?>" alt="Картинка">
        <?php } ?>
        <input id="upload_image_button" name="_add_picture" type="button" value="<?php if (isset($imgSrc)):?>Изменить картинку<?php else:?> Добавить картинку<?php endif;?>" class="upload_image_button">
        <input name="_delete_picture" type="button" value="Удалить картинку" class="remove_image_button">
    </p>
    <?php
    echo '</div>'; // Закрывающий тег Группировки полей
}
add_action('woocommerce_product_options_advanced', 'addCustomFields');

/**
 * Сохранение данных произвольльных полей методами Woocommerce
 *
 * @testedwith    WooCommerce 6.2.1
 * @verphp        7.4
 *
 */
function saveCustomFields($post_id)
{
    // Вызываем объект класса
    $product = wc_get_product($post_id);

    // Собираем данные
    $select_field = isset($_POST['_select_type_product']) ? sanitize_text_field($_POST['_select_type_product']) : '';
    $create_date = isset($_POST['_create_date']) ? $_POST['_create_date'] : '';

    // Сохраняем
    $product->update_meta_data('create_date', $create_date);
    $product->update_meta_data('select_type_product', $select_field);

    // Сохраняем все значения
    $product->save();
}
add_action('woocommerce_process_product_meta', 'saveCustomFields', 10);

/**
 * Событие обработки Ajax
 *
 * @testedwith    WooCommerce 6.2.1
 * @verphp        7.4
 *
 */
function getImgAjaxObjRequest()
{
    // Массив с результатом отработки события
    $result = [
        'status' => false,
        'content' => false
    ];

    if (isset($_REQUEST)) {
        // Прерываем выполнение функции
        if (!wp_verify_nonce($_REQUEST['security'], 'img-nonce')) {
            wp_die('Базовая защита не пройдена');
        }
        $object = json_decode(html_entity_decode(stripslashes($_REQUEST["content"])))[0];

        $product = wc_get_product($object->uploadedTo);
        $product->update_meta_data('photo', $object->id);
        $product->save();

        $result['status'] = true;
        $result['content'] = "Картинка успешно добавлена";
    } else {
        $result['status'] = false;
        $result['content'] = esc_html__('Данные утеряны.');
    }

    echo json_encode($result);
    wp_die();
}
add_action('wp_ajax_img_ajax_obj_request', 'getImgAjaxObjRequest');