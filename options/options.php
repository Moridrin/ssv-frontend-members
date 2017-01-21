<?php
function ssv_users_add_sub_menu()
{
    add_submenu_page('ssv_settings', 'Users Options', 'Users', 'manage_options', __FILE__, 'ssv_users_options_page_content');
}

function ssv_users_options_page_content()
{
    $active_tab = "general";
    if (isset($_GET['tab'])) {
        $active_tab = $_GET['tab'];
    }
    ?>
    <div class="wrap">
        <h1>Users Options</h1>
        <h2 class="nav-tab-wrapper">
            <a href="?page=<?= $_GET['page'] ?>&tab=general" class="nav-tab <?= $active_tab == 'general' ? 'nav-tab-active' : '' ?>">General</a>
            <a href="?page=<?= $_GET['page'] ?>&tab=users_page_columns" class="nav-tab <?= $active_tab == 'users_page_columns' ? 'nav-tab-active' : '' ?>">Users Page Columns</a>
            <a href="?page=<?= $_GET['page'] ?>&tab=email" class="nav-tab <?= $active_tab == 'email' ? 'nav-tab-active' : '' ?>">Email</a>
            <a href="http://2016.bosso.nl/ssv-users/" target="_blank" class="nav-tab">
                Help <img src="<?= SSV_Users::URL ?>general/images/link-new-tab.png" width="14px" style="vertical-align:middle">
            </a>
        </h2>
        <?php
        switch ($active_tab) {
            case "general":
                require_once "general.php";
                break;
            case "profile_page":
            case "register_page":
                break;
            case "users_page_columns":
                break;
            case "email":
                break;
        }
        ?>
    </div>
    <?php
}

add_action('admin_menu', 'ssv_users_add_sub_menu');

function ssv_users_general_options_page_content()
{
    ?><h2><a href="?page=<?= str_replace(SSV_Users::PATH, 'ssv-users/', __FILE__) ?>">Users Options</a></h2><?php
}

add_action(SSV_General::HOOK_GENERAL_OPTIONS_PAGE_CONTENT, 'ssv_users_general_options_page_content');
