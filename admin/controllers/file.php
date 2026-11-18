<?php
// Streams a private upload (payment screenshot, custom cake photo, chat file) to logged-in staff only.
require_admin();
serve_private_upload((string) ($_GET['path'] ?? ''));
