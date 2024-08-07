<?php

// регистрация кастомного типа постов Cities
add_action('init', 'custom_post');

function custom_post()
{
    register_post_type('cities', array(
        'labels' => array(
            'name' => 'Cities',
            'singular_name' => 'City',
            'add_new' => 'Добавить новый город',
            'add_new_item' => 'Добавить новый город',
            'edit_item' => 'Редактировать город',
            'new_item' => 'Новый город',
            'view_item' => 'Посмотреть город',
            'search_items' => 'Найти город',
            'not_found' => 'Город не найден',
            'not_found_in_trash' => 'В корзине город не найден',
            'parent_item_colon' => '',
            'menu_name' => 'Cities'
        ),
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'query_var' => true,
        'rewrite' => true,
        'capability_type' => 'post',
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => null,
        'supports' => array('title')
    ));
}


add_action('init', 'create_subjects_hierarchical_taxonomy', 0);

// регистрация таксономии Countries для Cities
function create_subjects_hierarchical_taxonomy()
{

    $labels = array(
        'name' => _x('Countries', 'taxonomy general name'),
        'singular_name' => _x('Country', 'taxonomy singular name'),
        'search_items' => __('Search Countries'),
        'all_items' => __('All Countries'),
        'parent_item' => __('Parent Country'),
        'parent_item_colon' => __('Parent Country:'),
        'edit_item' => __('Edit Country'),
        'update_item' => __('Update Country'),
        'add_new_item' => __('Add New Country'),
        'new_item_name' => __('New Country Name'),
        'menu_name' => __('Countries'),
    );

    register_taxonomy('countries', array('cities'), array(
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'show_in_rest' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'country'),
    ));

}

// подключаем функцию активации мета блока (my_extra_fields)
add_action('add_meta_boxes', 'my_extra_fields_meta_box', 1);

function my_extra_fields_meta_box()
{
    add_meta_box('extra_fields', 'Дополнительные поля', 'extra_fields_box_func', 'cities', 'normal', 'high');
}

// код блока
function extra_fields_box_func($post)
{
    ?>
    <div style="display: flex">
        <p style="width:30%">
            latitude:
            <label>
                <input type="number" step="any" name="extra[latitude]"
                       value="<?= get_post_meta($post->ID, 'latitude', 1) ?>"
                />
            </label>
        </p>
        <p style="width:30%">
            longitude:
            <label>
                <input type="number" step="any" name="extra[longitude]"
                       value="<?= get_post_meta($post->ID, 'longitude', 1) ?>"
                />
            </label>
        </p>
    </div>


    <input type="hidden" name="extra_fields_nonce" value="<?= wp_create_nonce('extra_fields_nonce_id') ?>"/>
    <?php
}

// обновление полей при сохранении
add_action('save_post', 'my_extra_fields_save_on_update', 0);

function my_extra_fields_save_on_update($post_id)
{
    // базовая проверка
    if (
        empty($_POST['extra'])
        || !wp_verify_nonce($_POST['extra_fields_nonce'], 'extra_fields_nonce_id')
        || wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)
    ) {
        return false;
    }

    $extra = $_POST['extra'];

    // сохранить/удалить данные

    // Очищаем все данные
    $extra = array_map('sanitize_text_field', $extra);
    foreach ($extra as $key => $value) {
        // удаляем поле если значение пустое
        if (!$value) {
            delete_post_meta($post_id, $key);
        } else {
            update_post_meta($post_id, $key, $value);
        }
    }

    return $post_id;
}

// получение погоды через APi
function getWeather($post_id)
{

    $lat = get_post_meta($post_id, 'latitude', true); // широта города
    $lon = get_post_meta($post_id, 'longitude', true); // долгота города

    $apiKey = '343cccbd8d344a028de170715240608';

    $url = 'https://api.weatherapi.com/v1/current.json?key='.$apiKey.'&q='.$lat.','.$lon;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    if ($data['error']['code']) {
        $error = false;
    } else {
        $error = true;
    }

    $result = array('data' => $data, 'error' => $error);

    return $result;
}

// Создание виджета погоды с выбором города из кастомного типа постов Cities
class trueTopPostsWidget extends WP_Widget
{
    function __construct()
    {
        parent::__construct(
            'cities',
            'Город с температурой', // заголовок виджета
            array('description' => 'Позволяет вывести город, и его температуру сейчас.') // описание
        );
    }

    // фронтэнд виджета

    public function widget($args, $instance)
    {
        if (isset($instance['post_id'])) {
            $cityID = $instance['post_id'];
        }
        $getWeather = getWeather($cityID);
        if ($getWeather['error']) {
            echo 'Погода в городе: '.$getWeather['data']['location']['name'].'<br>';
            echo 'Температура: '.$getWeather['data']['current']['temp_c'].'°C<br>';
        } else {
            echo 'Ошибка: '.$getWeather['data']['error']['message'];
        }

    }


    // бэкэнд виджета

    public function form($instance)
    {

        $post_type = !empty($instance['post_type']) ? $instance['post_type'] : '';
        $post_id = !empty($instance['post_id']) ? $instance['post_id'] : '';

        // Получаем список кастомных типов постов
        $post_types = get_post_types(array('public' => true, '_builtin' => false));
        $cities = get_posts([
            'post_type' => 'cities',
        ]);

        // Форма выбора типа поста
        echo '<p>';
        echo '<label for="'.$this->get_field_id('post_type').'">'.__('Тип поста:', 'text_domain').'</label>';
        echo '<select id="'.$this->get_field_id('post_type').'" name="'.$this->get_field_name('post_type').'">';
        foreach ($post_types as $type) {
            echo '<option value="'.$type.'"'.($post_type == $type ? 'elected="selected"' : '').'>'.$type.'</option>';
        }
        echo '</select>';
        echo '</p>';

        // Форма ввода городов

        echo '<p>';
        echo '<label for="'.$this->get_field_id('post_id').'">'.__('Выбрать город:', 'text_domain').'</label>';
        echo '<select id="'.$this->get_field_id('post_id').'" name="'.$this->get_field_name('post_id').'">';
        foreach ($cities as $city) {
            echo '<option value="'.$city->ID.'"'.($post_id == $city->ID ? 'selected="selected"' : '').'>'.$city->post_title.'</option>';
        }
        echo '</select>';
        echo '</p>';


    }

    // сохранение настроек виджета


    public function update($new_instance, $old_instance)
    {
        $instance = $old_instance;
        $instance['post_type'] = strip_tags($new_instance['post_type']);
        $instance['post_id'] = strip_tags($new_instance['post_id']);
        return $instance;
    }
}

// регистрация виджета

function true_top_posts_widget_load()
{
    register_widget('trueTopPostsWidget');
}

add_action('widgets_init', 'true_top_posts_widget_load');

// обработчик Ajax запроса поиска по названию города
function ajax_search_posts()
{
    $search_query = $_POST['search_query'];

    // готовим запрос учитывая значение введенное в поле поиска
    if ($search_query) {
        $args = array(
            's' => $search_query,
            'post_type' => 'cities',
            'posts_per_page' => -1
        );
    } else {
        $args = array(
            'post_type' => 'cities',
            'posts_per_page' => -1
        );
    }

    // получаем города
    $posts = get_posts($args);
    $results = array();
    foreach ($posts as $post) {

        // получаем погоду города
        $getWeather = getWeather($post->ID);

        // выводим страну в виде строки
        $terms = get_the_terms($post->ID, 'countries');
        $string = '';

        foreach ($terms as $value) {
            $string .= $value->name.' ';
        }

        // добавляем в массив полученные данные для передачи в скрипт
        $results[] = array(
            'country' => $string,
            'title' => $post->post_title,
            'weather' => $getWeather['data']['current']['temp_c'],
        );

    }
    wp_send_json($results);
}

add_action('wp_ajax_nopriv_ajax_search_posts', 'ajax_search_posts');
add_action('wp_ajax_ajax_search_posts', 'ajax_search_posts');