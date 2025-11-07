<?php

/**
 * Plugin Name:       سورناموتور
 * Plugin URI:        https://example.com/
 * Description:       مدیریت اقساط و تخفیفات products در ووکامرس
 * Version:           2.2.0
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            سعید
 * Author URI:        https://example.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       soorenamotor
 * Domain Path:       /languages
 * Requires Plugins:  woocommerce
 */

// اگر این فایل مستقیماً فراخوانی شود، از اجرای آن جلوگیری کن.
defined( 'ABSPATH' ) || exit;

// ثابت‌های اصلی پلاگین
define( 'SOORENAMOTOR_PLUGIN_FILE', __FILE__ );
define( 'SOORENAMOTOR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SOORENAMOTOR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SOORENAMOTOR_VERSION', '2.2.0' );

/**
 * @param class-string $class نام کامل کلاس (شامل namespace).
 * @return void
 */
function soorenamotor_autoloader(string $class): void {
    // فقط کلاس‌های مربوط به این پلاگین را بارگذاری کن.
    if (strpos( $class, 'Soorenamotor' ) !== 0) {
        return;
    }

    // namespace را حذف و اسلش‌ها را به DIRECTORY_SEPARATOR تبدیل کن.
    $class_file = str_replace( '\\', DIRECTORY_SEPARATOR, $class );
    $class_file = str_replace( 'Soorenamotor' . DIRECTORY_SEPARATOR, '', $class_file );
    $class_file = SOORENAMOTOR_PLUGIN_DIR . 'inc' . DIRECTORY_SEPARATOR . $class_file . '.php';

    if (file_exists( $class_file ) && is_readable( $class_file )) {
        require $class_file;
    }
}

// ثبت Autoloader
spl_autoload_register( 'soorenamotor_autoloader' );

register_activation_hook( SOORENAMOTOR_PLUGIN_FILE, function(): void {
    require_once SOORENAMOTOR_PLUGIN_DIR . 'inc/activator.php';
    soorenamotor_run_activation();
} );

/**
 * تابع اصلی برای اجرای پلاگین.
 *
 * این تابع نمونه‌هایی از کلاس‌های اصلی را ساخته و متدهای لازم را فراخوانی می‌کند.
 * @return void
 */
function soorenamotor_run(): void {
    // بررسی وجود ووکامرس
    if (!class_exists( 'WooCommerce' )) {
        add_action( 'admin_notices', 'soorenamotor_woocommerce_missing_notice' );
        return;
    }

    // ایجاد یک نمونه واحد از کلاس Calculator
    $calculator = new \Soorenamotor\Calculator();

    // اجرای کلاس‌های اصلی
    $admin = new \Soorenamotor\Admin();
    $admin->init();

    $cart = new \Soorenamotor\Cart($calculator);
    $cart->init();

    $block = new \Soorenamotor\Block($calculator);
    $block->init();
}

// اجرای پلاگین پس از بارگذاری تمام پلاگین‌ها
add_action( 'plugins_loaded', 'soorenamotor_run' );

/**
 * نمایش اعلان در صورت نصب نبودن ووکامرس.
 * @return void
 */
function soorenamotor_woocommerce_missing_notice(): void {
    ?>
    <div class="error notice">
        <p><?php esc_html_e( 'پلاگین "سورناموتور" به پلاگین ووکامرس برای عملکرد صحیح نیاز دارد. لطفاً ابتدا ووکامرس را نصب و فعال کنید.', 'soorenamotor' ); ?></p>
    </div>
    <?php
}
