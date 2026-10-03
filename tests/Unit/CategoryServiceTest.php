<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Services\ActivityLogService;
use App\Services\CategoryService;
use Mockery;
use Tests\TestCase;

class CategoryServiceTest extends TestCase
{
    private $categoryRepositoryMock;

    private $activityLogServiceMock;

    private CategoryService $categoryService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->categoryRepositoryMock = Mockery::mock(CategoryRepositoryInterface::class);
        $this->activityLogServiceMock = Mockery::mock(ActivityLogService::class);

        $this->categoryService = new CategoryService(
            $this->categoryRepositoryMock,
            $this->activityLogServiceMock
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_category_by_id_returns_category()
    {
        $category = new Category(['name' => 'Batik Tulis', 'slug' => 'batik-tulis']);
        $category->id = 1;

        $this->categoryRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($category);

        $result = $this->categoryService->getCategoryById(1);

        $this->assertSame($category, $result);
    }

    public function test_update_category_throws_exception_when_category_not_found()
    {
        $category = new Category;
        $category->id = 999;

        $this->categoryRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with(999)
            ->andReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Data kategori tidak ditemukan di sistem.');

        $this->categoryService->updateCategory($category, ['name' => 'Updated Name']);
    }

    public function test_delete_category_throws_exception_when_category_not_found()
    {
        $category = new Category;
        $category->id = 999;

        $this->categoryRepositoryMock
            ->shouldReceive('findById')
            ->once()
            ->with(999)
            ->andReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Data kategori tidak ditemukan di sistem.');

        $this->categoryService->deleteCategory($category);
    }
}
