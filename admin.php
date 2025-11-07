<?php

/**
 * Admin Class.
 *
 *تمام قابلیت‌های مربوط به بخش مدیریت افزونه را کنترل می‌کند 
 * @package Soorenamotor
 */

namespace Soorenamotor;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Admin
{

    /**
     * مقدار دهی اولیه تنظیمات  ادمین
     */
    public function init(): void
    {
        // افزودن فیلد سفارشی به تب اطلاعات کلی محصول
        add_action('woocommerce_product_options_general_product_data', [$this, 'add_installment_field']);
        // ذخیره‌سازی داده‌های فیلد سفارشی
        add_action('woocommerce_admin_process_product_object', [$this, 'save_installment_field']);
        // افزودن ستون سفارشی به فهرست محصولات
        add_filter('manage_edit-product_columns', [$this, 'add_installments_column'], 20);
        // نمایش داده‌ها در ستون سفارشی
        add_action('manage_product_posts_custom_column', [$this, 'display_installments_column_data'], 10, 2);
        // افزودن فیلد سفارشی به ویرایش سریع
        add_action('quick_edit_custom_box', [$this, 'add_installment_to_quick_edit'], 10, 2);
        // ذخیره‌سازی داده‌های ویرایش سریع
        add_action('save_post_product', [$this, 'save_quick_edit_data'], 10, 2);
        // افزودن CSS سفارشی برای ستون‌های ادمین
        add_action('admin_head', [$this, 'add_admin_css']);
        // افزودن جاوااسکریپت برای پر کردن خودکار فیلدهای ویرایش سریع
        add_action('admin_footer', [$this, 'add_quick_edit_js']);

        // Add a tools page for running quick Store API integration tests (local/dev use)
        add_action( 'admin_menu', [ $this, 'register_tools_page' ] );
    }

/**
     * فیلد "تعداد اقساط" را به گزینه‌های عمومی محصول اضافه می‌کند.
     */
    public function add_installment_field(): void
    {
        $product_id = get_the_ID();
        $value      = $product_id ? (int) get_post_meta($product_id, '_installments_number', true) : 0;

        woocommerce_wp_text_input(
            [
                'id'                => '_installments_number',
                'label'             => ' تعداد اقساط (روز)',
                'placeholder'       => '0',
                'desc_tip'          => true,
                'description'       => 'اگر خالی بماند یا صفر باشد، محصول نقدی محسوب می‌شود.',
                'type'              => 'number',
                'custom_attributes' => ['min' => 0, 'step' => 1],
                'value'             => max(0, $value),
            ]
        );
    }

    /**
     * ذخیره مقدار  فیلد تعداد اقساط
     *
     * @param WC_Product $product The product object.
     */
    public function save_installment_field(\WC_Product $product): void
    {
        $installments_number = filter_input(INPUT_POST, '_installments_number', FILTER_SANITIZE_NUMBER_INT);
        $product->update_meta_data('_installments_number', max(0, (int) $installments_number));
    }

    /**
     * افزودن ستون تعداد اقساط به جدول محصولات در پیشخوان
     *
     * @param array $columns Existing columns.
     * @return array Modified columns.
     */
    public function add_installments_column(array $columns): array
    {
        $new_columns = [];
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ('price' === $key) {
                $new_columns['installments'] = 'تعداد اقساط';
            }
        }
        return $new_columns;
    }

    /**
     * نمایش مقادیر در ستون تعداد اقساط در جدول محصولات 
     *
     * @param string $column  Column name.
     * @param int    $post_id Product ID.
     */
    public function display_installments_column_data(string $column, int $post_id): void
    {
        if ('installments' !== $column) {
            return;
        }

        $installments_number = (int) get_post_meta($post_id, '_installments_number', true);
        if ($installments_number > 0) {
            echo esc_html($installments_number . ' روز');
        } else {
            echo '<span style="color: #777;">نقدی</span>';
        }
    }

    /**
     * افزودن فیلد تعداد اقساط به ویرایش سریع.
     *
     * @param string $column_name Column name.
     * @param string $post_type   Post type.
     */
    public function add_installment_to_quick_edit(string $column_name, string $post_type): void
    {
        if ('installments' !== $column_name || 'product' !== $post_type) {
            return;
        }
?>
        <fieldset class="inline-edit-col-right">
            <div class="inline-edit-col">
                <label>
                    <span class="title">مدت بازپرداخت</span>
                    <span class="input-text-wrap">
                        <input type="number" name="_installments_number" class="text number" value="" min="0" step="1" placeholder="0">
                    </span>
                </label>
            </div>
        </fieldset>
        <?php
    }

    /**
     * ذخیره مقدار فیلد تعداد اقساط در ویرایش سریع
     *
     * @param int     $post_id Post ID.
     * @param WP_Post $post    Post object.
     */
    public function save_quick_edit_data(int $post_id, \WP_Post $post): void
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (! current_user_can('edit_post', $post_id)) {
            return;
        }
        if ('product' !== $post->post_type) {
            return;
        }

        $installments_number = filter_input(INPUT_POST, '_installments_number', FILTER_SANITIZE_NUMBER_INT);
        update_post_meta($post_id, '_installments_number', max(0, (int) $installments_number));
    }

    /**
     * افزودن استایل به ستون ادمین.
     */
    public function add_admin_css(): void
    {
        $screen = get_current_screen();
        if ($screen && 'edit-product' === $screen->id) {
        ?>
            <style>
                .widefat th#installments,
                .widefat td.column-installments {
                    width: 100px;
                    text-align: center;
                }
            </style>
        <?php
        }
    }

    /**
     * Add JavaScript to populate the quick edit field with the current value.
     */
    public function add_quick_edit_js(): void
    {
        $screen = get_current_screen();
        if ($screen && 'edit-product' === $screen->id) {
        ?>
            <script type="text/javascript">
                jQuery(window).on('load', function() {
                    jQuery('body').on('click', '.editinline', function() {
                        const postId = jQuery(this).closest('tr').attr('id').replace("post-", "");
                        const installmentValue = jQuery('#post-' + postId + ' .column-installments').text().trim();

                        // Populate the quick edit field
                        const inputField = jQuery('input[name="_installments_number"]');
                        if (installmentValue.includes('نقدی')) {
                            inputField.val('');
                        } else {
                            // Extract number from "X روز"
                            const number = installmentValue.replace(' روز', '');
                            inputField.val(number);
                        }
                    });
                });
            </script>
<?php
        }
    }
}
