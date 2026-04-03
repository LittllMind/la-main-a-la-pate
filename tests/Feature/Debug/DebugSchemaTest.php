<?php

namespace Tests\Feature\Debug;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DebugSchemaTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function debug_orders_columns(): void
    {
        $columns = Schema::getColumns('orders');
        dump('Orders columns:', array_column($columns, 'name'));
        
        $this->assertTrue(true);
    }
}
