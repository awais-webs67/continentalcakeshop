<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

// /llms.txt: a short plain summary of the shop, its pages and cakes for AI assistants
// (ChatGPT, Perplexity, Claude, Gemini). Built from the database, like the sitemap.

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: public, max-age=3600');
echo llms_txt();
