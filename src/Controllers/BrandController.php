<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Brand;
use App\Models\Image;
use App\Models\Location;

class BrandController extends Controller
{
    public function index(Request $request, array $params = []): void
    {
        $this->requirePermission('view_images');

        $brandModel = new Brand();
        $brands     = $brandModel->findAll([], 'name ASC');

        $locationModel = new Location();
        foreach ($brands as &$brand) {
            $brand['location_count'] = count($locationModel->findByBrand($brand['id']));
        }
        unset($brand);

        $this->render('brands/index', [
            'brands'     => $brands,
            'pageTitle'  => 'Marcas',
            'bodyClass'  => 'page-home',
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    public function locations(Request $request, array $params = []): void
    {
        $this->requirePermission('view_images');

        $brandId    = (int) ($params['id'] ?? 0);
        $brandModel = new Brand();
        $brand      = $brandModel->find($brandId);

        if (!$brand) {
            http_response_code(404);
            require __DIR__ . '/../Views/errors/404.php';
            exit;
        }

        $locationModel = new Location();
        $locations     = $locationModel->findByBrand($brandId);

        $imageModel = new Image();
        foreach ($locations as &$location) {
            $location['image_count'] = $imageModel->countByLocation($brandId, $location['id']);
            $previews = $imageModel->findByLocation($brandId, $location['id']);
            $location['preview_images'] = array_map(
                fn($img) => $this->enrichThumb($img, $brand['slug']),
                array_slice($previews, 0, 4)
            );
        }
        unset($location);

        $this->render('brands/locations', [
            'brand'      => $brand,
            'locations'  => $locations,
            'pageTitle'  => $brand['name'],
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    private function enrichThumb(array $image, string $brandSlug): array
    {
        $path = $image['thumb_filepath'] ?? '';
        if (str_starts_with($path, 'http')) {
            $image['thumb_url'] = $path;
        } else {
            $base = rtrim(env('APP_URL', ''), '/') . '/storage/images';
            $image['thumb_url'] = $path !== ''
                ? $base . '/' . $brandSlug . '/' . basename($path)
                : '';
        }
        return $image;
    }
}
