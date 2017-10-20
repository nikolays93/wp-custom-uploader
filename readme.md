#### Uploader must be initialize before script enqueues

```php
$this->uploader = new WP_Custom_Uploader( array(
    'assets_dir' => PLUGINNAME_URL . '/includes/classes/',
    'advanced_variable' => array(
    'user_id' => get_current_user_id(),
    ),
) );

$this->uploader->display();
```