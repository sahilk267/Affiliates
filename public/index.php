<?php
// ZenithSoles Affiliate Management System
// Entry point for public web requests

// If composer dependencies are installed and Laravel is present, bootstrap it.
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
	require __DIR__ . '/../vendor/autoload.php';

	$appPath = __DIR__ . '/../bootstrap/app.php';
	if (file_exists($appPath)) {
		$app = require $appPath;
		$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

		$response = $kernel->handle(
			$request = Illuminate\Http\Request::capture()
		);

		$response->send();
		$kernel->terminate($request, $response);
		exit;
	}
}

// Fallback message when Laravel is not installed
echo "ZenithSoles Affiliate Management System – API & Admin Panel (bootstrap not installed)";