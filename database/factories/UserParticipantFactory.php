<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserParticipant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserParticipant>
 */
class UserParticipantFactory extends Factory
{
    protected $model = UserParticipant::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'education_institution' => fake()->company(),
            'education_course' => fake()->jobTitle(),
            'professional_experience' => fake()->paragraph(),
            'skills' => ['PHP', 'Vue.js', 'Laravel'],
            'technologies' => ['Docker', 'PostgreSQL', 'Tailwind'],
            'github_url' => 'https://github.com/' . fake()->userName(),
            'linkedin_url' => 'https://linkedin.com/in/' . fake()->userName(),
            'is_profile_public' => true,
        ];
    }
}
