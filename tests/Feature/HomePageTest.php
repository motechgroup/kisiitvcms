<?php

namespace Tests\Feature;

use Database\Seeders\CategorySeeder;
use Database\Seeders\NewsContentSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SiteSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_can_be_rendered(): void
    {
        $this->seed([
            RoleAndPermissionSeeder::class,
            CategorySeeder::class,
            SiteSettingSeeder::class,
            NewsContentSeeder::class,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('KISII');
        $response->assertSee('News Network');
        $response->assertSee('Breaking News');
        $response->assertSee('Latest News Wire');
        $response->assertSee('Trending / Most Read');
        $response->assertSee('Politics & National Governance');
        $response->assertSee('Counties & Devolution Affairs');
        $response->assertSee('Business, Markets & Economy');
        $response->assertSee('Entertainment, Music & Culture');
        $response->assertSee('Lifestyle, Wellness & Travel');
        $response->assertSee('Featured Video Reports & Broadcast Bulletins');
        $response->assertSee('Editorial Board & Thought Leadership');
    }
}
