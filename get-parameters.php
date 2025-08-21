<?php
// Loads DB connection parameters from AWS Systems Manager Parameter Store
// Exposes: $ep (endpoint), $un (username), $pw (password), $db (database)

require_once __DIR__ . '/aws.phar';

$ep = $un = $pw = $db = '';

try {
    // Discover region from IMDS; fall back to env or us-east-1
    $az = @file_get_contents('http://169.254.169.254/latest/meta-data/placement/availability-zone');
    $region = $az ? substr(trim($az), 0, -1) : (getenv('AWS_DEFAULT_REGION') ?: 'us-east-1');

    $ssm = new Aws\Ssm\SsmClient([
        'version' => 'latest',
        'region'  => $region
    ]);

    $resp = $ssm->getParameters([
        'Names' => ['/example/endpoint','/example/username','/example/password','/example/database'],
        'WithDecryption' => true
    ]);

    $vals = [];
    foreach ($resp['Parameters'] as $p) {
        $vals[$p['Name']] = $p['Value'];
    }

    $ep = $vals['/example/endpoint']  ?? '';
    $un = $vals['/example/username']  ?? '';
    $pw = $vals['/example/password']  ?? '';
    $db = $vals['/example/database']  ?? '';

    if (!$ep || !$un || !$db) {
        throw new Exception('Missing one or more /example/* parameters');
    }
} catch (Throwable $e) {
    error_log('SSM parameter fetch error: ' . $e->getMessage());
}
