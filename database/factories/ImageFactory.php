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


$factory->define(App\Models\Image::class, function (Faker\Generator $faker) {
    $width = $faker->numberBetween(400, 1000);
    $height = $faker->numberBetween(400, 1000);
    return [
        'url' => $faker->imageUrl($width, $height),
        'width' => $width,
        'height' => $height,
        'size' => $faker->randomNumber($nbDigits = NULL, $strict = false),
        'mime' => $faker->randomElement($array = array (0, 1, 2))
    ];
});
