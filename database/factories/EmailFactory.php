<?php

namespace Database\Factories;

use App\Models\Email;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class EmailFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Email::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'uuid' => Str::uuid()->toString(),
            'from' => $this->faker->safeEmail,
            'to' => $this->faker->safeEmail,
            'subject' => $this->faker->sentence,
            'text_content' => $this->faker->paragraph,
            'html_content' => $this->faker->randomHtml(),
            'attachments' => json_encode([]),
        ];
    }
}
