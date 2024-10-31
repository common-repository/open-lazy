<?php
/*
 * Plugin Name: Open Lazy
 * Version: 2.6
 * Description: A handy toolkit can easily tweak up and speed up your wordpress, more simple, more natural. 
 * Plugin URI: https://www.xiaomac.com/2015101692.html
 * Author: Link
 * Author URI: https://www.xiaomac.com/
 * Text Domain: open-lazy
 * Domain Path: /lang
*/

function olop($k, $v=null){
    if(!isset($GLOBALS['olop'])) $GLOBALS['olop'] = get_option('olop');
    return isset($GLOBALS['olop'][$k]) ? (isset($v) ? $GLOBALS['olop'][$k] == $v : $GLOBALS['olop'][$k]) : '';
}

add_action('init', 'open_lazy_init', 1);
function open_lazy_init(){
    add_action('get_header', 'open_lazy_get_header');
    if(!is_admin()){
        if(olop('open_lazy_unload_wp_head')){
            $remove = explode(',', olop('open_lazy_unload_wp_head'));
            foreach($remove as $k){
                $tk = trim($k);
                if($tk=='feed_links') remove_action('wp_head', $tk, 2);
                else if($tk=='feed_links_extra') remove_action('wp_head', $tk, 3);
                else if($tk=='print_emoji_detection_script') remove_action('wp_head', $tk, 7);
                else remove_action('wp_head', $tk);
            }
        }
        if(olop('open_lazy_ui_emoji')) remove_action('wp_print_styles', 'print_emoji_styles');
    }else{
        if(olop('open_lazy_ui_emoji')){
            remove_action('admin_print_scripts', 'print_emoji_detection_script');
            remove_action('admin_print_styles', 'print_emoji_styles');
        }
    }
    if(olop('open_lazy_pack')){
        if(isset($_GET['action'])){
            if($_GET['action']==md5(olop('open_lazy_pack_path_ver').'_cache')){
                add_action('wp_enqueue_scripts', 'open_lazy_pack_action_cache', 10000);
            }
            if($_GET['action']==md5(olop('open_lazy_pack_path_ver').'_clear')){
                add_action('wp_enqueue_scripts', 'open_lazy_pack_action_clear', 10000);
            }
        }else{
            add_action('wp_enqueue_scripts', 'open_lazy_pack_action', 10000);
            add_action('login_enqueue_scripts', 'open_lazy_pack_action', 10000);
        }
    }
    if(olop('open_lazy_ext_login_page')){
        add_filter('login_headertext', function(){ return get_option('blogname'); });
        add_filter('login_headerurl', function(){ return home_url(); });
    }
    if(olop('open_lazy_ui_open_sans')){
        add_filter('gettext_with_context', 'open_lazy_ui_open_sans_action', 888, 4);
    }
    if(olop('open_lazy_html')){
        add_filter('the_content', 'open_lazy_html_action');
        if(olop('open_lazy_html_wp_head')) add_action('wp_head',function(){echo olop('open_lazy_html_wp_head');});
        if(olop('open_lazy_html_wp_footer')) add_action('wp_footer',function(){echo olop('open_lazy_html_wp_footer');});
        if(olop('open_lazy_html_login_head')) add_action('login_head',function(){echo olop('open_lazy_html_login_head');});
    }
    if(olop('open_lazy_html_load')){
        add_action('wp_footer', 'open_lazy_html_load_action');
    }
    if(olop('open_lazy_ext_remove_version')){
        add_filter('script_loader_src', function($src){return remove_query_arg('ver', $src);});
        add_filter('style_loader_src', function($src){return remove_query_arg('ver', $src);});
    }
    if(olop('open_lazy_ext_disable_revision')){
        if(!defined('WP_POST_REVISIONS')) define('WP_POST_REVISIONS', false);
        add_filter('wp_revisions_to_keep', 'open_lazy_ext_disable_revision_action', 10, 2);
        function open_lazy_ext_disable_revision_action($num, $post) { return 0; }
    }
    if(olop('open_lazy_ext_widget_shortcode')){
        add_filter('widget_text', 'do_shortcode');
    }
    if(olop('open_lazy_prefetch')){
        add_filter('wp_resource_hints', 'open_lazy_prefecth_action', 11, 2);
    }
    if(olop('open_lazy_ext_attachment_redirect')){
        add_action('template_redirect', 'open_lazy_ext_attachment_redirect_action', 2);
    }
    if(olop('open_lazy_ext_disable_heartbeat')){
        wp_deregister_script('heartbeat');
    }
}

add_action('admin_init', 'open_lazy_admin_init', 1);
function open_lazy_admin_init() {
    load_plugin_textdomain('open-lazy', '', dirname(plugin_basename(__FILE__)) . '/lang');
    register_setting('open_lazy_admin_options_group', 'olop');
    add_filter('pre_update_option_olop', 'open_lazy_admin_options_save', 10, 2);
    add_filter('pre_set_site_transient_update_plugins', 'open_lazy_inactive_update', 10, 2);
    add_action('admin_bar_menu', 'open_lazy_admin_bar_action', 999);
    if(olop('open_lazy_unload_dashboard')){
        add_action('wp_dashboard_setup', 'open_lazy_unload_dashboard_action', 11);
    }
    if(olop('open_lazy_maintenance')){
        add_action('admin_head', function(){ 
            echo '<style>#wp-admin-bar-olop-indicator.Enabled {background: #9F0000}</style>';
        });
    }
}

function open_lazy_data($key){
    $data = get_plugin_data( __FILE__ );
    return isset($data) && is_array($data) && isset($data[$key]) ? $data[$key] : '';
}

function open_lazy_admin_options_save($new_value, $old_value){
    if(isset($old_value['pack']['style'])||isset($old_value['pack']['script'])) $new_value['pack'] = $old_value['pack'];
    return $new_value;
}

add_action('admin_menu', 'open_lazy_admin_add_page');
function open_lazy_admin_add_page() {
    add_options_page(__('Open Lazy','open-lazy'), __('Open Lazy','open-lazy'), 'manage_options', 'open-lazy', 'open_lazy_admin_options_page');
}

add_filter('plugin_action_links_'.plugin_basename( __FILE__ ), 'open_lazy_settings_link');
function open_lazy_settings_link($links) {
    return array_merge(array(open_lazy_link('options-general.php?page=open-lazy', __('Settings', 'open-lazy'))), $links);
}

function open_lazy_link($url, $text='', $ext=''){
    if(empty($text)) $text = $url;
    $button = stripos($ext, 'button') !== false ? " class='button'" : "";
    $target = stripos($ext, 'blank') !== false ? " target='_blank'" : "";
    $link = "<a href='{$url}'{$button}{$target}>{$text}</a>";
    return stripos($ext, 'p') !== false ? "<p>{$link}</p>" : "{$link} ";
}

add_action('current_screen', 'open_lazy_setting_screen');
function open_lazy_setting_screen() {
    $screen = get_current_screen();
    if($screen->id != 'settings_page_open-lazy') return;
    $help_content = '<p>'.open_lazy_data('Description').'</p><br/>'.
        '<p>'.open_lazy_link('//wordpress.org/plugins/open-lazy/', __('Plugin Rating', 'open-lazy'), 'button,blank').'</p>';
    $help_sidebar = '<p><strong>'.__('For more information', 'open-lazy').'</strong></p>'.
        open_lazy_link(open_lazy_data('PluginURI'), __('Check Update', 'open-lazy'), 'p,blank').
        open_lazy_link('//www.xiaomac.com/about', __('Donate', 'open-lazy'), 'p,blank').
        open_lazy_link('//www.xiaomac.com/tag/work', __('More Plugins', 'open-lazy'), 'p,blank');
    $screen->add_help_tab(array('id' => 'open_lazy_help', 'title' => __('About', 'open-lazy'), 'content' => $help_content));
    $screen->set_help_sidebar($help_sidebar);
}

function open_lazy_get_header() {
    if(olop('open_lazy_maintenance')){
        if(!is_super_admin() || (isset($_GET['preview']) && $_GET['preview'] == md5('maintenance'))){
            $content = olop('open_lazy_maintenance_content');
            $content = apply_filters('the_content', $content);
            wp_die($content, get_bloginfo('name').' - '.__('Maintenance Mode', 'open-lazy'), array('response'=>'503'));
        }
    }
    if(olop('open_lazy_html_compress')){
        ob_start('open_lazy_html_compress_action');
    }
}

function open_lazy_html_compress_action($buffer) {
    $buffer_arr = explode("\n", $buffer);
    $buffer_out = '';
    $count = count($buffer_arr);
    for ($i = 0; $i <= $count; $i++) {
        $buffer_arr[$i] = str_replace("\t", " ", $buffer_arr[$i]);
        $buffer_arr[$i] = trim($buffer_arr[$i]);
        $len = strlen($buffer_arr[$i]);
        if($len>0) $buffer_out .= $buffer_arr[$i]."\n";
    }
    $buffer_out = trim($buffer_out);
    return $buffer_out;
}

function open_lazy_html_load_action() {
    $content = ' ['.date('Y-m-d H:i:s', time()+3600*8).'] ';
    $content .= __('Loaded: ','open-lazy').timer_stop().' / '.__('Queries: ','open-lazy'). get_num_queries();
    echo current_user_can('administrator') ? "<small> $content </small>" : "<!-- $content -->";
}

function open_lazy_html_action($content){
    if(olop('open_lazy_html_content_find') && olop('open_lazy_html_content_replace')){
        $find  = explode(',',olop('open_lazy_html_content_find'));
        $replace = explode(',',olop('open_lazy_html_content_replace'));
        $content = str_replace($find, $replace, $content);
    }
    if(is_single()) $content = olop('open_lazy_html_content_head').$content.olop('open_lazy_html_content_footer');
    return $content;
}

add_action('wp_enqueue_scripts', 'open_lazy_script_style_action', 100);
add_action('login_enqueue_scripts', 'open_lazy_script_style_action', 100);
add_action('admin_enqueue_scripts', 'open_lazy_script_style_action', 100);
function open_lazy_script_style_action() {
    if(olop('open_lazy_ui_font_awesome')){
        wp_enqueue_style('open-lazy-font-awesome', plugins_url('ui/font-awesome.min.css', __FILE__));
    }
    if(olop('open_lazy_pack') && olop('open_lazy_pack_style_disable')){
        $disable = explode(',', olop('open_lazy_pack_style_disable'));
        foreach ($disable as $key) { wp_deregister_style(trim($key)); }
    }
    if(olop('open_lazy_ext_word_count')){
        wp_localize_script('word-count', 'wordCountL10n', array(
            'type' => _x('characters_excluding_spaces', 'Word count type. Do not translate!'),
            'shortcodes' => ! empty($GLOBALS['shortcode_tags']) ? array_keys($GLOBALS['shortcode_tags']) : array()
        ));
    }
}

function open_lazy_pack_action() {
    $pack = olop('pack');
    $upload = wp_get_upload_dir();
    if(olop('open_lazy_pack_style_path') && is_array($pack) && count($pack['style']['packed'])>0){
        global $wp_styles;
        foreach($pack['style']['packed'] as $kv){ $wp_styles->done[] = $kv['name']; }
        wp_enqueue_style('open-lazy-style', add_query_arg('v',olop('open_lazy_pack_path_ver'),$upload['baseurl'].olop('open_lazy_pack_style_path')));
    }
    if(olop('open_lazy_pack_script_path') && is_array($pack) && count($pack['script']['packed'])>0){
        global $wp_scripts;
        foreach($pack['script']['packed'] as $kv){ $wp_scripts->done[] = $kv['name']; }
        wp_enqueue_script('open-lazy-script',add_query_arg('v',olop('open_lazy_pack_path_ver'),$upload['baseurl'].olop('open_lazy_pack_script_path')),array(),false,(olop('open_lazy_pack_script_footer',1)));
    }
}

function open_lazy_pack_action_cache(){
    if(!is_super_admin()) return;
    date_default_timezone_set(get_option('timezone_string'));
    $upload = wp_get_upload_dir();
    $txt = $err = '';
    $arr_style_unpack = array();
    $arr_style_packed = array();
    $arr_script_unpack = array();
    $arr_script_packed = array();
    $olops = get_option('olop');
    if(olop('open_lazy_pack_style_path')){
        global $wp_styles;
        $filter = olop('open_lazy_pack_style_filter');
        $queue = $wp_styles->queue;
        $wp_styles->all_deps($queue);
        foreach($wp_styles->to_do as $key => $handle){
            if (!in_array($handle, $wp_styles->done, true) && isset($wp_styles->registered[$handle])) {
                if(!$wp_styles->registered[$handle]->src) continue;
                $src = html_entity_decode( $wp_styles->registered[$handle]->src );
                if(isset($wp_styles->registered[$handle]->extra['conditional'])||($filter&&open_lazy_pack_check_exclude($filter, $handle, $src))){
                    $arr_style_unpack[] = array('name'=> $handle, 'url' => $src);
                    continue;
                }
                if(false === stripos($src, '//')) $src = home_url($src, is_ssl()?'https':'http');
                if(false === stripos($src, ':')) $src = set_url_scheme($src, is_ssl()?'https':'http');
                $txt .= "/* $handle: ($src) */\n";
                $_remote_get = wp_remote_get(add_query_arg('v', rand(1, 9999999), $src),array('sslverify'=>false));
                if(! is_wp_error($_remote_get) && $_remote_get['response']['code'] == 200){
                    $arr_style_packed[] = array( 'name'=> $handle, 'url' => $src );
                    $content = open_lazy_pack_style_zipper($_remote_get['body'], $src);
                    if(isset($wp_styles->registered[$handle]->extra['after'])){
                        $content = $content . "\n" . implode("\n", $wp_styles->registered[$handle]->extra['after']);
                    }
                    $txt .= $content."\n\n";
                }else{
                    $err .= "/*\nError: $src\n";
                    $err .= "HTTP Code: {$_remote_get['response']['code']} ({$_remote_get['response']['message']})\n*/\n\n";
                }
            }
        }
        if(count($arr_style_unpack)+count($arr_style_packed)){
            $olops['pack']['style'] = array('packed'=>$arr_style_packed,'unpack'=>$arr_style_unpack);
        }
        $txt = "/* Cache: ".date('Y-m-d H:i:s')." */\n" . $txt;
        $filepath = $upload['basedir'].olop('open_lazy_pack_style_path');
        $ok = wp_upload_bits('ol-temp-'.time().'.css', null, $txt);
        if(!empty($ok['error'])){
            $err .= "/* Error: ($filepath) */\n";  
        }else{
            @rename($ok['file'], $filepath);
        }
    }
    if(olop('open_lazy_pack_script_path')){
        //wp_enqueue_script('comment-reply');
        global $wp_scripts;
        $filter = olop('open_lazy_pack_script_filter');
        $txt = '';
        $queue = $wp_scripts->queue;
        $wp_scripts->all_deps($queue);
        foreach($wp_scripts->to_do as $key => $handle){
            if (!in_array($handle, $wp_scripts->done, true) && isset($wp_scripts->registered[$handle])) {
                if(!$wp_scripts->registered[$handle]->src) continue;
                $src = html_entity_decode($wp_scripts->registered[$handle]->src);
                if($filter && open_lazy_pack_check_exclude($filter, $handle, $src)){
                    $arr_script_unpack[] = array('name'=> $handle, 'url' => $src);
                    continue;
                }
                if(isset($wp_scripts->registered[$handle]->extra['data'])){
                    $txt .= "/* $handle */\n" . $wp_scripts->registered[$handle]->extra['data'] . "\n";
                }
                if(false === stripos($src, '//')) $src = home_url($src, is_ssl()?'https':'http');
                if(false === stripos($src, ':')) $src = set_url_scheme($src, is_ssl()?'https':'http');
                $txt .= "/* $handle: ($src) */\n";
                $_remote_get = wp_remote_get(add_query_arg('v', rand(1, 9999999), $src),array('sslverify'=>false));
                if(!is_wp_error($_remote_get) && $_remote_get['response']['code'] == 200){
                    $arr_script_packed[] = array( 'name'=> $handle, 'url' => $src );
                    $txt .= $_remote_get['body'].";\n\n";
                }else{
                    $err .= "/*\nError: $src\n";
                    $err .= "HTTP Code: {$_remote_get['response']['code']} ({$_remote_get['response']['message']})\n*/\n\n";
                }
            }
        }
        if(count($arr_script_unpack)+count($arr_script_packed)){
            $olops['pack']['script'] = array('packed'=>$arr_script_packed,'unpack'=>$arr_script_unpack);
        }
        $txt = "/* Cache: ".date('Y-m-d H:i:s')." */\n" . $txt;
        $filepath = $upload['basedir'].olop('open_lazy_pack_script_path');
        $ok = wp_upload_bits('ol-temp-'.time().'.js', null, $txt);
        if(!empty($ok['error'])){
            $err .= "/* Error: ($filepath) */\n";  
        }else{
            @rename($ok['file'], $filepath);
        }
    }
    if(!$err){
        $olops['open_lazy_pack_path_ver'] = time()+3600*8;
        update_option('olop', $olops);
    }
    echo $err ? $err : '<script>window.opener.location.reload();window.close();</script>';
    exit();
}

function open_lazy_pack_action_clear(){
    if(!is_super_admin()) return;
    $err = '';
    $olops = get_option('olop');
    $upload = wp_get_upload_dir();
    if(olop('open_lazy_pack_style_path')){
        $path = $upload['basedir'].olop('open_lazy_pack_style_path');
        if(!unlink($path)) $err = 'Clear Err: ' . $path . '\n';
        unset($olops['pack']['style']);
    }
    if(olop('open_lazy_pack_script_path')){
        $path = $upload['basedir'].olop('open_lazy_pack_script_path');
        if(!unlink($path)) $err = 'Clear Err: ' . $path . '\n';
        unset($olops['pack']['script']);
    }
    update_option('olop', $olops);
    echo $err ? $err : '<script>window.opener.location.reload();window.close();</script>';
    exit();
}

function open_lazy_var_dump($mixed=null) {
    ob_start();
    var_dump($mixed);
    $content = ob_get_contents();
    ob_end_clean();
    return $content;
}

function open_lazy_pack_style_zipper($css, $path) {
    $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', ' ', $css);
    $css = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), ' ', $css);
    $css = str_replace(array(';}', ' {', '} ', ': ', ' !', ', ', ' >', '> '),array('}',  '{',  '}',  ':',  '!',  ',',  '>',  '>'), $css);
    $dir = dirname($path).'/';
    $css = preg_replace('|url\(\'?"?([a-zA-Z0-9=#\?\&\-_\s\./]*)\'?"?\)|', "url(\"$dir$1\")", $css);
    return $css;
}

function open_lazy_pack_check_exclude($filter, $handle, $src){
    if(!$filter) return false;
    $arr = explode(',',$filter);
    return (in_array($handle, $arr) || in_array(basename($src), $arr));
}

function open_lazy_prefecth_action($urls, $relation_type){
    if('dns-prefetch' === $relation_type){
        $dns_arr = explode("\n", trim(olop('open_lazy_prefetch_dns')));
        foreach($dns_arr as $dns){ if(trim($dns)) $urls[] = trim($dns); }
    }
    return $urls;
}

function open_lazy_ext_attachment_redirect_action(){
    global $post;
    $post_id = is_attachment() && isset($post->post_parent) ? $post->post_parent : 0;
    if(is_numeric($post_id) && $post_id>0){
        wp_redirect(get_permalink($post_id), 301);
        exit();
    }
}

add_action('http_api_curl', 'open_lazy_http_api_curl', 10, 3);
function open_lazy_http_api_curl(&$handle, $args, $url){
    if($api = trim(olop('open_lazy_replace_api'))){
        curl_setopt($handle, CURLOPT_URL, str_replace('https://api.wordpress.org/', $api, $url));
    }
    return $handle;
}

add_filter('locale', 'open_lazy_locale');
function open_lazy_locale($lang){
    if(!olop('open_lazy_ext_auto_lang') || $lang != 'en_US') return $lang;
    if($user = get_current_user_id()){
        if($locale = get_user_meta($user, 'locale', true)) return $locale;
    }
    if(isset($_SESSION['WPLANG'])) return $_SESSION['WPLANG'];
    if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])){
        list($hal) = explode(',', strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']));
        foreach(get_available_languages() as $k){
            if(stripos(str_replace('_', '-', $k), $hal) !== false){
                $lang = $_SESSION['WPLANG'] = $k;
                break;
            }
        }
    }
    return $lang;
}

function open_lazy_ui_open_sans_action($translations, $text, $context, $domain){
    if('Open Sans font: on or off' == $context && 'on' == $text) $translations = 'off';
    return $translations;
}

function open_lazy_admin_bar_action($wp_admin_bar){
    if(olop('open_lazy_ext_view_site_new')){
        $wp_admin_bar->add_node(array('id'=>'view-site', 'parent'=>false, 'meta'=>array('target'=>'_blank')));
    }
    if(olop('open_lazy_unload_admin_bar')){
        $remove = explode(',',olop('open_lazy_unload_admin_bar'));
        foreach($remove as $k){ $wp_admin_bar->remove_node(trim($k)); }
    }
    if(olop('open_lazy_maintenance') && is_super_admin()){
        $indicator = array(
            'id' => 'olop-indicator',
            'title' => __('Maintenance Mode','open-lazy').': '.__('Enabled', 'open-lazy'),
            'parent' => false,
            'href' => get_admin_url(null, 'options-general.php?page=open-lazy'),
            'meta' => array(
                'title' => __('Maintenance Mode','open-lazy'),
                'class' => __('Enabled', 'open-lazy')
            )
        );
        $wp_admin_bar->add_node($indicator);
    }
}

function open_lazy_unload_dashboard_action(){
    $remove = explode(',',olop('open_lazy_unload_dashboard'));
    foreach($remove as $k){
        $tk = trim($k);
        if($tk=='dashboard_incoming_links'||$tk=='dashboard_plugins') remove_meta_box( $tk,'dashboard','normal' );
        else remove_meta_box( $tk,'dashboard','side' );
    }
}

function open_lazy_inactive_update($val, $key){
    if(!olop('open_lazy_ext_active_update') && !olop('open_lazy_ext_inactive_update')) return $val;
    if(empty($val->response) || !is_admin() || !is_super_admin()) return $val;
    foreach ($val->response as $k => $v){
        if(is_plugin_active_for_network($k)) continue;
        $active = is_plugin_active($k) ? true : false;
        if($active && !olop('open_lazy_ext_active_update')) continue;
        if(!$active && !olop('open_lazy_ext_inactive_update')) continue;
        unset($val->response[$k]);
    }
    return $val;
}

function open_lazy_admin_options_page(){
    $upload = wp_upload_dir();
    ?>
    <div class="wrap">
        <h1><?php _e('Open Lazy','open-lazy')?><a class="page-title-action" href="<?php echo open_lazy_data('PluginURI');?>" target="_blank"><?php echo open_lazy_data('Version');?></a>
        </h1>
        <form action="options.php" method="post">
        <?php settings_fields('open_lazy_admin_options_group'); ?>
        <h2 class="nav-tab-wrapper">
            <a class="nav-tab nav-tab-active" href="javascript:void(0);"><?php _e('Tweak','open-social');?></a>
            <a class="nav-tab" href="javascript:void(0);"><?php _e('Maintain','open-social')?></a>
            <a class="nav-tab" href="javascript:void(0);"><?php _e('Misc','open-social')?></a>
        </h2>
        <table class="form-table">
        <tr valign="top"><th scope="row"><?php _e('Pack','open-lazy')?></th>
        <td><fieldset>
            <label><input name="olop[open_lazy_pack]" type="checkbox" value="1" <?php checked(olop('open_lazy_pack'),1);?> /> <?php _e('Enabled','open-lazy')?></label> &nbsp;
            <label><input name="olop[open_lazy_pack_script_footer]" type="checkbox" value="1" <?php checked(olop('open_lazy_pack_script_footer'),1);?> /> <?php _e('Load in footer','open-lazy')?></label>
            <br/>
            <label><input name="olop[open_lazy_pack_style_path]" size="80" placeholder="/cache/style.css" value="<?php echo olop('open_lazy_pack_style_path')?>" /> <?php _e('Style Path','open-lazy')?></label>
            <?php if(olop('open_lazy_pack_style_path')) echo '<a href="'.add_query_arg('v', olop('open_lazy_pack_path_ver'), $upload['baseurl'].olop('open_lazy_pack_style_path')).'" target=_blank>?</a>';?>
            <br/>
            <label><input name="olop[open_lazy_pack_script_path]" size="80" placeholder="/cache/script.js" value="<?php echo olop('open_lazy_pack_script_path')?>" /> <?php _e('Script Path','open-lazy')?></label>
            <?php if(olop('open_lazy_pack_script_path')) echo '<a href="'.add_query_arg('v', olop('open_lazy_pack_path_ver'), $upload['baseurl'].olop('open_lazy_pack_script_path')).'" target=_blank>?</a>';?>
            <br/>
            <label>
                <input name="olop[open_lazy_pack_style_filter]" size="40" placeholder="some-style-handle,another-name.css" value="<?php echo olop('open_lazy_pack_style_filter')?>" />
                <input name="olop[open_lazy_pack_script_filter]" size="36" placeholder="some-script-handle,another-name.js" value="<?php echo olop('open_lazy_pack_script_filter')?>" /> <?php _e('Style / Script unpack','open-lazy')?>
            </label><br/>
            <label><input name="olop[open_lazy_pack_style_disable]" size="80" placeholder="open-sans,twentytwelve-fonts" value="<?php echo olop('open_lazy_pack_style_disable')?>" /> <?php _e('Styles unload','open-lazy')?></label>
            </fieldset>
            <?php if(olop('open_lazy_pack')): ?>
                <p><a href="/?action=<?php echo md5(olop('open_lazy_pack_path_ver').'_cache');?>" class="button" target="_blank"><?php _e('Generate Packer','open-lazy')?></a>
                <a href="/?action=<?php echo md5(olop('open_lazy_pack_path_ver').'_clear');?>" class="button" target="_blank"><?php _e('Delete Packer','open-lazy')?></a> 
                <input type="hidden" name="olop[open_lazy_pack_path_ver]" value="<?php echo olop('open_lazy_pack_path_ver')?>"></p>
            <?php endif; ?>
            <style>.open_lazy_table .bold{font-weight:bold;}.open_lazy_table td{padding:8px;font-size:12px;text-overflow:ellipsis;overflow:hidden;white-space:nowrap;}</style>
            <?php
            $pack = olop('pack');
            if(is_array($pack) && count($pack,1)>2){
                if(count($pack['style'],1)>2){
                    $code = '<br/><table class="widefat fixed open_lazy_table" cellpadding="3" cellspacing="0" style="width:85%">';
                    $code .= '<tr class="bold"><td width=150>#handle</td><td width=120>#name</td><td class="nowrap">#url</td><td width=60></td></tr>';
                    foreach($pack['style']['packed'] as $kv) {
                        $code .= '<tr class="alternate"><td>'.$kv['name'].'</td><td>'.basename($kv['url']).'</td><td class="nowrap">'.$kv['url'].'</td><td>'.__('Packed','open-lazy').'</td></tr>';
                    }
                    foreach($pack['style']['unpack'] as $kv) {
                        $code .= '<tr><td>'.$kv['name'].'</td><td>'.basename($kv['url']).'</td><td class="nowrap">'.$kv['url'].'</td><td>'.__('Unpack','open-lazy').'</td></tr>';
                    }
                    echo $code . '</table>';
                }
                if(count($pack['script'],1)>2){
                    $code = '<br/><table class="widefat fixed open_lazy_table" cellpadding="3" cellspacing="0" style="width:85%">';
                    $code .= '<tr class="bold"><td width=150>#handle</td><td width=120>#name</td><td class="nowrap">#url</td><td width=60></td></tr>';
                    foreach($pack['script']['packed'] as $kv) {
                        $code .= '<tr class="alternate"><td>'.$kv['name'].'</td><td>'.basename($kv['url']).'</td><td class="nowrap">'.$kv['url'].'</td><td>'.__('Packed','open-lazy').'</td></tr>';
                    }
                    foreach($pack['script']['unpack'] as $kv) {
                        $code .= '<tr><td>'.$kv['name'].'</td><td>'.basename($kv['url']).'</td><td class="nowrap">'.$kv['url'].'</td><td>'.__('Unpack','open-lazy').'</td></tr>';
                    }
                    echo $code . '</table>';
                }
            }?>
        </td></tr>
        <tr valign="top"><th scope="row"><?php _e('Prefetch','open-lazy')?></th>
        <td><fieldset>
            <label><input name="olop[open_lazy_prefetch]" type="checkbox" value="1" <?php checked(olop('open_lazy_prefetch'),1);?> /> <?php _e('Enabled','open-lazy')?></label> <br/>
            <textarea name="olop[open_lazy_prefetch_dns]" rows="5" cols="80" placeholder="//s.w.org"><?php echo esc_textarea(olop('open_lazy_prefetch_dns')); ?></textarea><br/>
        </fieldset>
        </td></tr>
        <tr valign="top"><th scope="row"><?php _e('Unload','open-lazy')?></th>
        <td><fieldset>
            <label>
            <textarea name="olop[open_lazy_unload_wp_head]" rows="4" cols="80" placeholder="wp-head,feed_links"><?php echo esc_textarea(olop('open_lazy_unload_wp_head'));?></textarea></label><br/>
            <label><input name="olop[open_lazy_unload_dashboard]" size="81" placeholder="wp_dashboard_setup" value="<?php echo olop('open_lazy_unload_dashboard')?>" /></label><br/>
            <label><input name="olop[open_lazy_unload_admin_bar]" size="81" placeholder="admin_bar_menu" value="<?php echo olop('open_lazy_unload_admin_bar')?>" /></label>
            <p><a href="//wordpress.org/support/topic/remove-feed-from-wp_head" target="_blank" class="button">#wp_head</a>
            <a href="//codex.wordpress.org/Plugin_API/Action_Reference/wp_dashboard_setup" target="_blank" class="button">#wp_dashboard_setup</a>
            <a href="//codex.wordpress.org/Function_Reference/remove_node" target="_blank" class="button">#admin_bar_menu</a></p>
        </fieldset>
        </td></tr>
        </table>

        <table class="form-table">
        <tr valign="top"><th scope="row"><?php _e('HTML','open-lazy')?></th>
        <td><fieldset>
            <label><input name="olop[open_lazy_html]" type="checkbox" value="1" <?php checked(olop('open_lazy_html'),1);?> /> <?php _e('Enabled','open-lazy')?></label> &nbsp;
            <label><input name="olop[open_lazy_html_compress]" type="checkbox" value="1" <?php checked(olop('open_lazy_html_compress'),1);?> /> <?php _e('HTML compress','open-lazy')?></label> &nbsp;
            <label><input name="olop[open_lazy_html_load]" type="checkbox" value="1" <?php checked(olop('open_lazy_html_load'),1);?> /> <?php _e('Display loaded speed','open-lazy')?></label> <br/>
            <label><input name="olop[open_lazy_html_content_find]" size="80" placeholder="a,b,c" value="<?php echo olop('open_lazy_html_content_find')?>" /> <?php _e('Find Strings in Content','open-lazy')?></label><br/>
            <label><input name="olop[open_lazy_html_content_replace]" size="80" placeholder="x,y,z" value="<?php echo olop('open_lazy_html_content_replace')?>" /> <?php _e('Replace Strings in Content','open-lazy')?></label><br/>
            <textarea name="olop[open_lazy_html_wp_head]" rows="2" cols="80" placeholder="WP Head"><?php echo esc_textarea( olop('open_lazy_html_wp_head') ) ?></textarea><br/>
            <textarea name="olop[open_lazy_html_content_head]" rows="2" cols="80" placeholder="Content Head"><?php echo esc_textarea( olop('open_lazy_html_content_head') ) ?></textarea><br/>
            <textarea name="olop[open_lazy_html_content_footer]" rows="5" cols="80" placeholder="Content Footer"><?php echo esc_textarea( olop('open_lazy_html_content_footer') ) ?></textarea><br/>
            <textarea name="olop[open_lazy_html_wp_footer]" rows="3" cols="80" placeholder="WP Footer"><?php echo esc_textarea( olop('open_lazy_html_wp_footer') ) ?></textarea><br/>
            <textarea name="olop[open_lazy_html_login_head]" rows="2" cols="80" placeholder="Login Head"><?php echo esc_textarea( olop('open_lazy_html_login_head') ) ?></textarea><br/>
        </fieldset>
        </td></tr>
        <tr valign="top"><th scope="row"><?php _e('Maintenance Mode','open-lazy')?></th>
        <td><fieldset>
            <label><input name="olop[open_lazy_maintenance]" type="checkbox" value="1" <?php checked(olop('open_lazy_maintenance'),1);?> /> <?php _e('Enabled','open-lazy')?></label> <br/>
            <textarea name="olop[open_lazy_maintenance_content]" rows="5" cols="80" placeholder=""><?php echo olop('open_lazy_maintenance_content')?esc_textarea( olop('open_lazy_maintenance_content') ):__('<h1>Website Under Maintenance</h1><p>Please come back later.</p>','open-lazy') ?></textarea><br/>
            <?php if(olop('open_lazy_maintenance')): ?>
            <small><?php _e('Remember to flush your cache if some cache plugins installed','open-lazy')?></small><br/><br/>
            <p><a href="/?preview=<?php echo md5('maintenance');?>" class="button" target="_blank"><?php _e('Preview','open-lazy')?></a></p>
            <?php endif; ?>
        </fieldset>
        </td></tr>
        <tr valign="top"><th scope="row"><?php _e('Replace API','open-lazy')?></th>
        <td><fieldset>
            <label><input name="olop[open_lazy_replace_api]" size="80" placeholder="https://api.wordpress.org/" value="<?php echo olop('open_lazy_replace_api')?>" /></label><br/>
        </fieldset>
        </td></tr>
        </table>
        <table class="form-table">
        <tr valign="top"><th scope="row"><?php _e('UI','open-lazy')?></th>
        <td><fieldset>
            <label><input name="olop[open_lazy_ui_emoji]" type="checkbox" value="1" <?php checked(olop('open_lazy_ui_emoji'),1);?> /> <?php _e('Remove Emoji','open-lazy')?></label> <br/>
            <label><input name="olop[open_lazy_ui_open_sans]" type="checkbox" value="1" <?php checked(olop('open_lazy_ui_open_sans'),1);?> /> <?php _e('Disable Open-Sans','open-lazy')?></label> <br/>
            <?php if (!wp_style_is('font-awesome','registered') && !wp_style_is('font-awesome-styles','registered')): ?>
                <label><input name="olop[open_lazy_ui_font_awesome]" type="checkbox" value="1" <?php checked(olop('open_lazy_ui_font_awesome'),1);?> /> <?php _e('Enable Font-Awesome','open-lazy')?>
                <a href="http://fontawesome.io/" target="_blank">?</a></label> <br/>
            <?php endif; ?>
        </fieldset>
        </td></tr>
        <tr valign="top"><th scope="row"><?php _e('Extension','open-lazy')?></th>
        <td><fieldset>
            <label><input name="olop[open_lazy_ext_login_page]" type="checkbox" value="1" <?php checked(olop('open_lazy_ext_login_page'),1);?> /> <?php _e('Filter login page linker to local site','open-lazy')?></label><br/>
            <label><input name="olop[open_lazy_ext_remove_version]" type="checkbox" value="1" <?php checked(olop('open_lazy_ext_remove_version'),1);?> /> <?php _e('Remove version of resource file','open-lazy')?></label> <br/>
            <label><input name="olop[open_lazy_ext_view_site_new]" type="checkbox" value="1" <?php checked(olop('open_lazy_ext_view_site_new'),1);?> /> <?php _e('Make admin bar visit-site link top and new','open-lazy')?></label> <br/>
            <label><input name="olop[open_lazy_ext_word_count]" type="checkbox" value="1" <?php checked(olop('open_lazy_ext_word_count'),1);?> /> <?php _e('Fix word count for East Asian characters','open-lazy')?></label> <br/>
            <label><input name="olop[open_lazy_ext_disable_revision]" type="checkbox" value="1" <?php checked(olop('open_lazy_ext_disable_revision'),1);?> /> <?php _e('Disable post revisions','open-lazy')?></label> <br/>
            <label><input name="olop[open_lazy_ext_widget_shortcode]" type="checkbox" value="1" <?php checked(olop('open_lazy_ext_widget_shortcode'),1);?> /> <?php _e('Enable shortcodes in sidebar widgets','open-lazy')?></label> <br/>
            <label><input name="olop[open_lazy_ext_attachment_redirect]" type="checkbox" value="1" <?php checked(olop('open_lazy_ext_attachment_redirect'),1);?> /> <?php _e('Redirect attachment page to the post attached','open-lazy')?></label> <br/>
            <label><input name="olop[open_lazy_ext_auto_lang]" type="checkbox" value="1" <?php checked(olop('open_lazy_ext_auto_lang'),1);?> /> <?php _e('Adapted to browser language when none was set','open-lazy')?></label> <br/>
            <label><input name="olop[open_lazy_ext_active_update]" type="checkbox" value="1" <?php checked(olop('open_lazy_ext_active_update'),1);?> /> <?php _e('Disable update notification of plugins that active','open-lazy')?></label> <br/>
            <label><input name="olop[open_lazy_ext_inactive_update]" type="checkbox" value="1" <?php checked(olop('open_lazy_ext_inactive_update'),1);?> /> <?php _e('Disable update notification of plugins that inactive','open-lazy')?></label> <br/>
            <label><input name="olop[open_lazy_ext_disable_heartbeat]" type="checkbox" value="1" <?php checked(olop('open_lazy_ext_disable_heartbeat'),1);?> /> <?php _e('Disable heartbeat in admin-panel','open-lazy')?></label> <br/>
        </fieldset>
        </td></tr>
        </table>
        <?php submit_button();?>
        </form>
        <script>
            jQuery('a.nav-tab').on('click', function(e){
                var idx = jQuery(this).index('a.nav-tab');
                jQuery('a.nav-tab').removeClass('nav-tab-active').eq(idx).addClass('nav-tab-active').blur();
                jQuery('.form-table').hide().eq(idx).show();
                if(window.localStorage) localStorage.setItem('open_lazy_tab', idx);
            });
            jQuery(function(){
                var tab = window.localStorage ? localStorage.getItem('open_lazy_tab') : 0;
                jQuery('a.nav-tab').eq(tab*1).click();
            });
        </script>
    </div>
    <?php
}

?>