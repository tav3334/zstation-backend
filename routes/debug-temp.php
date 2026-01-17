<?php
Route::get('/debug/machine-code', function () {
    $machineFile = file_get_contents(app_path('Models/Machine.php'));
    return response()->json([
        'file_exists' => file_exists(app_path('Models/Machine.php')),
        'file_hash' => md5($machineFile),
        'line_count' => count(file(app_path('Models/Machine.php'))),
        'last_10_lines' => array_slice(file(app_path('Models/Machine.php')), -10)
    ]);
});
