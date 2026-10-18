<?php
// Inline copy of assets/img/logo.svg so the Cinzel web font applies to the lettering.
echo str_replace('<svg ', '<svg class="logo" width="96" height="96" ', trim((string) file_get_contents(BASE_PATH . '/public/assets/img/logo.svg')));
