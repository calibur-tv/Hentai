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


$factory->define(App\Models\Pin::class, function (Faker\Generator $faker) {
    $inTrial = $faker->boolean();
    $isCreate = $faker->boolean();
    return [
        'user_id' => \App\User::inRandomOrder()->first()->id,
        'title' => $faker->text($maxNbChars = 20),
        'content' => $faker->realText($maxNbChars = 250, $indexSize = 2),
        'is_locked' => $faker->boolean(),
        'is_secret' => $faker->boolean(),
        'copyright_type' => $faker->randomElement($array = array (0, 1, 2, 3)),
        'is_create' => $isCreate,
        'origin_url' => $isCreate ? '' : $faker->url,
        'trial_type' => $inTrial ? $faker->randomElement($array = array (1, 2, 3)) : 0
    ];
});

$factory->afterCreating(App\Models\Pin::class, function ($pin, $faker) {
    $imageCount = $faker->numberBetween(0, 20);
    $tagCount = $faker->numberBetween(0, 20);
    $images = factory(App\Models\Image::class, $imageCount)->create();
    $now = \Carbon\Carbon::now();
    foreach ($images as $image)
    {
        \Illuminate\Support\Facades\DB::table('pin_image')->insert([
            'image_id' => $image->id,
            'pin_id' => $pin->id
        ]);
    }
    for ($i = 0; $i < $tagCount; $i++)
    {
        $tagId = \App\Models\Tag::inRandomOrder()->first()->id;
        \App\Models\Tag::where('id', $tagId)->increment('use_count');
        \Illuminate\Support\Facades\DB::table('pin_tag')->insert([
            'tag_id' => $tagId,
            'user_id' => \App\User::inRandomOrder()->first()->id,
            'pin_id' => $pin->id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
    $pin->update([
        'slug' => base_convert(($pin->id * 1000 + rand(0, 999)), 10, 36),
        'image_count' => $imageCount,
        'tag_count' => $tagCount
    ]);
});
