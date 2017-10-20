<?php

class WP_Custom_Uploader
{
    const VERSION = '1.0';

    private $properties = array();

    /**
     * support one uploader on page.
     *
     * @todo fixit!
     * @see display, _enqueues
     */
    function __construct( $params = array() )
    {
        $this->properties = wp_parse_args( $params, array(
            'max_upload_size'   => wp_max_upload_size(),
            'type'              => 'image',
            'multiple'          => false,
            'nopriv'            => false,
            'action_name'       => 'upload_new_image',
            'nonce_name'        => 'upload_new_image',
            'assets_dir'        => '',
            'advanced_variable' => array(),
            ) );

        if( $this->properties['nopriv'] ) {
            add_action( 'wp_ajax_nopriv_' . $this->get('action_name'), array($this, 'ajax_upload_new_file') );
            add_action( 'wp_enqueue_scripts', array($this, '_enqueues') );
        }
        else {
            add_action( 'admin_enqueue_scripts', array($this, '_enqueues') );
        }

        add_action( 'wp_ajax_' . $this->get('action_name'), array($this, 'ajax_upload_new_file') );
    }

    public function get( $param = false )
    {
        if( $param === false ) {
            return $this->properties;
        }

        return isset( $this->properties[ $param ] ) ? $this->properties[ $param ] : false;
    }

    public function set( $param = null, $value = null )
    {
        if( ! $param || ! $value ) {
            return;
        }

        $this->properties[ $param ] = $value;
    }

    public function display()
    {

        $user = wp_get_current_user();

        $class = '';
        if( $url = get_user_meta( $user->ID, 'uploaded-image', true ) ) {
            $class = " hidden";
        }
        ?>
        <div id="plupload-upload-ui" class="hide-if-no-js drag-drop<?php echo $class;?>">
            <div id="drag-drop-area" style="position: relative;">
                <div class="drag-drop-inside">
                    <p class="drag-drop-info">Перетащите изображение</p>
                    <p>или</p>
                    <p class="drag-drop-buttons">
                        <input id="plupload-browse-button" type="button" value="Выберите на компьютере" class="button" onclick="document.getElementById('upload-image').click();">
                    </p>

                    <p id="response-msg"></p>
                </div>
            </div>
            <p>Максимальный размер файла: <?php echo size_format( absint( $this->get('max_upload_size') ) ) ?></p>
        </div>

        <div id="uploaded-response">
            <?php
            if( $url ){
                $style = ' style="max-width: 100%;height: auto;cursor: pointer;"';
                $onclick = ' onclick="document.getElementById(\'upload-image\').click();"';

                echo sprintf('<img src="%s"%s%s>', esc_url($url), $style, $onclick );
            }
            ?>
        </div>

        <input type="hidden" name="uploaded-result">
        <input id="upload-image" type="file" multiple="false" accept="" style="display: none;">
        <?php
    }

    function _enqueues() {
        wp_enqueue_script( 'upload_image', $this->get('assets_dir') . 'custom-uploader.js',
            array( 'jquery' ), self::VERSION, true );

        $variables = array(
            'action'   => $this->get('action_name'),
            'nonce'    => wp_create_nonce( $this->get('nonce_name') ),
            'max_size' => $this->get('max_upload_size'),
            );

        if( ($advanced = $this->get( 'advanced_variable' )) && is_array($advanced) ) {
            $variables = array_merge($advanced, $variables);
        }

        wp_localize_script( 'upload_image', 'upload_props', $variables);
    }

    function ajax_upload_new_file() {
        if( ! isset( $_GET['nonce']) || ! wp_verify_nonce( $_GET['nonce'], $this->get('nonce_name') ) ) {
            wp_die( 'Нарушены правила безопасности', 'Ошибка!' );
        }

        reset( $_FILES );
        $filekey = key( $_FILES );

        $FILE = $_FILES[ $filekey ];
        if( $FILE['error'] ) {
            wp_die( 'Случилась непредвидення ошибка', 'Ошибка!' );
        }

        $arrFilename = explode('.', $FILE['name']);
        if( ! in_array( end($arrFilename), array( 'jpg', 'jpeg', 'png', 'gif' ) ) ) {
            wp_die( 'Загружено не изображение', 'Ошибка!' );
        }

        if( strpos($FILE['type'], 'image') !== 0 ) {
            wp_die( 'Загружено не изображение', 'Ошибка!' );
        }

        if( $FILE['size'] > $this->get('max_upload_size') ) {
            wp_die( 'Превышен лиминт веса изображения', 'Ошибка!' );
        }

        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/media.php' );

        $attachment_id = media_handle_upload( $filekey, 0);
        if ( is_wp_error( $attachment_id ) ) {
            $answer['err'] = 1;
            $answer['msg'] = "Ошибка загрузки медиафайла.";
        }
        else {
            $answer['url'] = wp_get_attachment_url( $attachment_id );

            if( isset($_GET['user_id']) ) {
                update_user_meta( absint($_GET['user_id']), 'uploaded-image', $answer['url'] );
            }
        }

        echo json_encode( wp_parse_args( $answer, array( 'err' => 0, 'msg' => '', 'url' => '' ) ) );
        wp_die();
    }
}