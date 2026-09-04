<?php

use App\InstalledVersion;

return [
    'version' => InstalledVersion::detect(base_path('VERSION'), env('FINANCEIRO_VERSION', 'dev')),
    'author' => 'Felipe Valtl de Mello',
    'repository' => 'https://github.com/valtlfelipe/financeiro',
    'sponsors' => 'https://apoia.felipevm.com/financeiro',
    'license' => 'AGPL-3.0-only',
];
