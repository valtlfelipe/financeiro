<?php

$trusted = array_values(array_filter(array_map(
    trim(...),
    explode(',', (string) env('TRUSTED_PROXIES', '')),
)));

return [
    'trusted' => $trusted,
];
