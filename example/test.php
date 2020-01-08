<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/TestKernel.php';

$kernel = new TestKernel();
$kernel->boot();
$logger = $kernel->getContainer()->get('test_logger');

$logger->info('Info message', ['field' => 'value']);
$logger->error('Error message', [
    'exception' => new InvalidArgumentException('Something happened'),
    'param' => 'something',
]);

echo "OK\n";
