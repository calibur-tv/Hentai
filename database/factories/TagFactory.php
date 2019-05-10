<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

use Illuminate\Support\Str;

$factory->define(App\Models\Tag::class, function (Faker\Generator $faker) {
    return [
        'creator_id' => \App\User::inRandomOrder()->first()->id,
        'name' => $faker->userName,
        'avatar' => $faker->imageUrl(200, 200),
        'parent_slug' => '1e1'
    ];
});

$factory->afterCreating(App\Models\Tag::class, function ($tag, $faker) {
    $tag->update([
        'slug' => base_convert(($tag->id * 1000 + rand(0, 999)), 10, 36)
    ]);
});
