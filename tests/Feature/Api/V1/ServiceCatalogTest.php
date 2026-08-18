<?php

namespace Tests\Feature\Api\V1;

use App\Models\ServiceCategory;
use App\Models\ServiceType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_active_services(): void
    {
        $activeService = ServiceType::factory()->create(['is_active' => true]);
        $inactiveService = ServiceType::factory()->create(['is_active' => false]);

        $response = $this->getJson(route('api.v1.services.index'));

        $response->assertOk()
            ->assertJsonFragment(['id' => $activeService->id])
            ->assertJsonMissing(['id' => $inactiveService->id]);
    }

    public function test_can_search_services_by_name(): void
    {
        $service1 = ServiceType::factory()->create(['name' => 'Passport Application', 'is_active' => true]);
        $service2 = ServiceType::factory()->create(['name' => 'Driving License', 'is_active' => true]);

        $response = $this->getJson(route('api.v1.services.index', ['search' => 'Passport']));

        $response->assertOk()
            ->assertJsonFragment(['id' => $service1->id])
            ->assertJsonMissing(['id' => $service2->id]);
    }

    public function test_can_filter_services_by_category(): void
    {
        $category1 = ServiceCategory::factory()->create();
        $category2 = ServiceCategory::factory()->create();

        $service1 = ServiceType::factory()->create(['category_id' => $category1->id, 'is_active' => true]);
        $service2 = ServiceType::factory()->create(['category_id' => $category2->id, 'is_active' => true]);

        $response = $this->getJson(route('api.v1.services.index', ['category_id' => $category1->id]));

        $response->assertOk()
            ->assertJsonFragment(['id' => $service1->id])
            ->assertJsonMissing(['id' => $service2->id]);
    }

    public function test_can_show_service_details(): void
    {
        $service = ServiceType::factory()->create(['is_active' => true]);

        $response = $this->getJson(route('api.v1.services.show', $service));

        $response->assertOk()
            ->assertJsonPath('data.id', $service->id);
    }

    public function test_can_list_categories(): void
    {
        $category = ServiceCategory::factory()->create();

        $response = $this->getJson(route('api.v1.services.categories'));

        $response->assertOk()
            ->assertJsonFragment(['id' => $category->id]);
    }
}
