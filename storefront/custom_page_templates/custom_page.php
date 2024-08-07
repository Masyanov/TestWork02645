<?php
/*
* Template name: Таблица с городами
* Template post type: page
*/

get_header();
?>
    <div id="primary" class="content-area">
        <main id="main" class="site-main" role="main">
            <h1>Таблица городов</h1>
            <div class="search">
                <form id="search-form">
                    <input id="search-input" type="text" placeholder="Поиск...">
                    <button type="submit">Искать</button>
                    <div id="search-results" style="margin-top: 30px">
                        <?php
                        // получаем все опубликованные города из таблицы wp_posts по типу поста cities
                        global $wpdb;
                        $cities = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}posts WHERE post_type = 'cities' AND post_status = 'publish'");
                        ?>
                        <table id="search-results_table">
                            <thead>
                            <tr>
                                <th scope="col">Страна</th>
                                <th scope="col">Город</th>
                                <th scope="col">Температура</th>
                            </tr>
                            </thead>
                            <tbody id="search-results_table_tbody">
                            <?php foreach ($cities as $city) {
                                // получаем погоду по API
                                $getWeather = getWeather($city->ID);
                                ?>
                                <tr>
                                    <td>
                                        <?php
                                        // выводим таксономию в виде строки
                                        $terms = get_the_terms($city->ID, 'countries');
                                        $string = '';

                                        foreach ($terms as $value) {
                                            $string .= $value->name.' ';
                                        }
                                        echo $string;
                                        ?>
                                    </td>
                                    <td>
                                        <?php echo $city->post_title ?>
                                    </td>
                                    <td>
                                        <?php
                                        // null значит координаты не введены или не найдены
                                        if ($getWeather['data']['current']['temp_c']) {
                                            echo $getWeather['data']['current']['temp_c'];
                                        } else {
                                            echo 'null';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
        </main><!-- #main -->
    </div><!-- #primary -->
<?php
do_action('storefront_sidebar');
get_footer();
