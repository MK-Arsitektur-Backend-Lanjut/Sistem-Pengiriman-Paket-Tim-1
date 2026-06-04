<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DebugRequest extends Command
{
    protected $signature = 'debug:request';
    protected $description = 'Debug middleware stack dan request JSON parsing';

    public function handle(): int
    {
        $this->info('=== REQUEST DEBUG ===');
        
        // Cek apakah ForceJsonResponse terdaftar di middleware
        $kernel = app(\Illuminate\Foundation\Http\Kernel::class);
        $middlewareGroups = $kernel->getMiddlewareGroups();
        
        $this->info('Middleware group "api":');
        if (isset($middlewareGroups['api'])) {
            foreach ($middlewareGroups['api'] as $m) {
                $this->line("  - {$m}");
            }
        } else {
            $this->warn('  (tidak ada group api)');
        }

        // Simulasi parsing JSON body
        $this->newLine();
        $this->info('--- Test JSON Parsing ---');
        
        $jsonBody = '{"warehouse_name":"Debug Test","location":"Debug City","capacity":500,"current_load":0,"status":"active"}';
        $parsed = json_decode($jsonBody, true);
        
        $request = \Illuminate\Http\Request::create(
            '/api/v1/warehouse',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            $jsonBody
        );
        
        $this->info('[json_decode] Result: ' . json_encode($parsed));
        $this->info('[Request->all()] Result: ' . json_encode($request->all()));
        $this->info('[Request->input(warehouse_name)] Result: ' . $request->input('warehouse_name'));
        $this->info('[Request->json()->all()] Result: ' . json_encode($request->json()->all()));
        
        if (empty($request->all())) {
            $this->error('[FAIL] $request->all() kosong! JSON body tidak terparsing.');
            $this->warn('       Ini adalah akar masalah silent failure!');
        } else {
            $this->info('[PASS] $request->all() berisi data dengan benar.');
        }
        
        return 0;
    }
}
