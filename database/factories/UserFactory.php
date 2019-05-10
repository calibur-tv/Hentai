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

$factory->define(App\User::class, function (Faker\Generator $faker) {
    return [
        'nickname' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'avatar' => $faker->imageUrl(200, 200),
        'password' => Str::random(32), // secret
        'api_token' => Str::random(32),
    ];
});

$factory->afterCreating(App\User::class, function ($user, $faker) {
    $user->update([
        'slug' => base_convert(($user->id * 1000 + rand(0, 999)), 10, 36)
    ]);
});
