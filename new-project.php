#! /usr/bin/env php
<?php

$short_opts  = "";
$short_opts .= "f:";  // Required value
$short_opts .= "v::"; // Optional value

$long_opts  = array(
    "required:",     // Required value
    "theme::",    // Optional value
);

#Presets
$standard_files = "/Users/dan.flies/code/wordpress-default";
$plugins_to_activate = ['contact-form-7', 'robo-gallery', 'advanced-custom-fields'];
$theme_to_activate = "startup-blog";
$option_settings = [
    'timezone_string' => 'America/Chicago',
    'date_format' => 'Y-m-d',
    'time_format' => 'H:i',
    'comments_notify' => 0,
    'moderation_notify' => 0,
    'default_comment_status' => 'closed',
    'show_avatars' => 0,
    'permalink_structure' => ' /%postname%/',
];

# Get starting point
$project_name = $argv[1];
$script_working_dir = getcwd();

if (!file_exists($project_name)) {
    log_message("Project directory created.");
    mkdir($project_name, 0777, true);
}

$url = "{$project_name}.localhost";
if(!file_exists("/Applications/AMPPS/www/$url")){
    symlink("{$script_working_dir}/{$project_name}/","/Applications/AMPPS/www/$url" );
}

//file_put_contents("./{$project_name}/wordpress.tar.gz", "https://wordpress.org/latest.tar.gz");

chdir($project_name);
if(!file_exists('wp-config.php')) {
    log_message("wp-config created.");
    exec("wp config create --dbname='{$project_name}_wp_db' --dbuser=root --dbpass=mysql --dbhost=127.0.0.1");
} else {
    log_message("wp-config already exisits.");
}

$is_installed = exec("wp core is-installed");
if(!$is_installed){
    log_message("Installing WordPress");
    exec("wp core install --url={$url} --title={$project_name} --admin_user='dfadmin' --admin_password='default_pass' --admin_email=dan@danflies.com");
} else {
    log_message("WP is already installed.");
}

$themes = scandir("{$standard_files}/themes");
$plugins = scandir("{$standard_files}/plugins");

log_message("Installing themes.");
foreach ($themes as $theme) {
    if($theme !== '.' && $theme !== '..'){
        log_message("\t{$theme}");
        exec("wp theme install {$standard_files}/themes/{$theme} 2> /dev/null");
    }
}

log_message("Installing Plugins:");
foreach ($plugins as $plugin) {
    if($plugin !== '.' && $plugin !== '..'){
        log_message("\t$plugin");
        exec("wp plugin install {$standard_files}/plugins/{$plugin} 2> /dev/null");
    }
}

if(!empty($theme_to_activate)){
    exec("wp theme is-active {$theme_to_activate}", $op_active_theme, $xc_active_theme);
    if($xc_active_theme > 0){
        log_message("Activating theme: {$theme_to_activate}");
        exec("wp theme activate {$theme_to_activate}");
    }
}

exec("wp plugin list --fields=name,status --format=json 2> /dev/null", $plugin_list, $xc_plugin);

if ($xc_plugin == 0) {
    $plugin_list_json = json_decode($plugin_list[0]);
    $left_to_activate = array_filter($plugin_list_json, function ($value) use ($plugins_to_activate) {
        return in_array($value->name, $plugins_to_activate) && $value->status != 'active';
    }, ARRAY_FILTER_USE_BOTH);
}

if (!empty($left_to_activate)) {
    log_message("Activating plugins:");
    $remaining_plugins_to_activate = array_column($left_to_activate, 'name');
    foreach ($remaining_plugins_to_activate as $the_plugin) {
        log_message("\t{$the_plugin}");
        exec("wp plugin activate {$the_plugin}");
    }
}

log_message("Changing Default Settings");
foreach ($option_settings as $key => $setting){
    exec("wp option set {$key} {$setting}", $op_setting, $xc_setting);
    if($xc_setting > 0){
        log_message("\tSetting: {$key} failed to be set");
    } else {
        log_message("\t{$key} set");
    }
}

function log_message($message){
    $now = date("Y-m-d H:i:s");
    echo "[$now]\t$message\n";
}