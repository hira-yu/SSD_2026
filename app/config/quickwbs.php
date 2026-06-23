<?php

declare(strict_types=1);

return [
    'base_url' => rtrim((string) env('QUICKWBS_API_BASE', 'https://quickwbs.hirayu.jp/api'), '/'),
    'agent_docs_url' => (string) env('QUICKWBS_AGENT_DOCS_URL', 'https://quickwbs.hirayu.jp/api/agent/docs'),
    'api_token' => (string) env('QUICKWBS_API_TOKEN', ''),
    'timeout' => 15,
];
