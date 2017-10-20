jQuery(document).ready(function($) {
    /**
     * @global upload_props
     */
    var $dropArea = $('#drag-drop-area'),
        $responseArea = $dropArea.find('#response-msg'),
        maxFileSize = upload_props.max_size;

        if (typeof(window.FileReader) == 'undefined') {
            $responseArea.text('Не поддерживается браузером!');
            $dropArea.addClass('err');
        }

        $dropArea[0].ondragover = function() {
            $dropArea.parent().addClass('drap-over');
            return false;
        };

        $dropArea[0].ondragleave = function() {
            $dropArea.parent().removeClass('drap-over');
            return false;
        };

        $dropArea[0].ondrop = function(event) {
            event.preventDefault();
            $dropArea.removeClass('hover');
            $dropArea.addClass('drop');

            sendFile( event.dataTransfer.files[0] );
        }

        $('#upload-image').on('change', function(event) {
            sendFile( $( this ).prop('files')[0] );
        });

        function sendFile(file) {
            if( ! file ) {
                $responseArea.text('Случилась непредвидення ошибка');
                return false;
            }

            if (file.size > maxFileSize) {
                $responseArea.text('Файл слишком большой!');
                $dropArea.addClass('err');
                return false;
            }

            if( ! file.type.match(/image\/(jpg|jpeg|png|gif)$/i) ) {
                $responseArea.text('Передано не изображение!');
                $dropArea.addClass('err');
                return false;
            }

            var xhr = new XMLHttpRequest();
            xhr.upload.addEventListener('progress', uploadProgress, false);
            xhr.onreadystatechange = stateChange;

            xhr.open('POST', ajaxurl + '?' + $.param(upload_props));
            xhr.setRequestHeader('X-FILE-NAME', file.name);

            var fd = new FormData();
            fd.append('img', file);
            xhr.send(fd);
        }

        function uploadProgress(event) {
            var percent = parseInt(event.loaded / event.total * 100);
            $responseArea.text('Загрузка: ' + percent + '%');
        }

        function stateChange(event) {
            if (event.target.readyState == 4) {
                var answer = JSON.parse(event.target.response);
                if (event.target.status == 200) {
                    $responseArea.text('Загрузка успешно завершена!');

                    if( ! event.target.response.err ) {
                        var style = ' style="max-width: 100%;height: auto;cursor: pointer;"',
                            onclick = ' onclick="document.getElementById(\'upload-image\').click();"';

                        $dropArea.parent().slideUp();
                        $('#uploaded-response').html( '<img src="'+answer.url+'"'+style+onclick+'>' );
                    }
                } else {
                    $responseArea.text('Произошла ошибка!');
                    $dropArea.addClass('err');
                }

            }
        }
});