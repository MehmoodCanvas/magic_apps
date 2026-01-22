<?php

namespace Database\Seeders;

use App\Models\AcademicSubject;
use Illuminate\Database\Seeder;

class AcademicSubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subjects = [
            'Mathematics',
            'Science',
            'Humanities',
            'Business',
            'Engineering',
            'Arts',
            'Social Sciences',
            'Computer Science',
            'Health Sciences',
            'Education',
            'Law',
            'Environmental Studies',
            'Psychology',
            'Communications',
            'Design',
            'Economics',
            'Political Science',
            'History',
        ];

        foreach ($subjects as $subject) {
            AcademicSubject::create([
                'name' => $subject,
                'status' => 'active',
            ]);
        }
    }
}
