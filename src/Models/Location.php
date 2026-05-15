<?php

declare(strict_types=1);

namespace App\Models;

class Location extends Model
{
    protected string $table = 'locations';

    public function findBySlug(string $slug): ?array
    {
        return $this->findBy('slug', $slug);
    }
}
