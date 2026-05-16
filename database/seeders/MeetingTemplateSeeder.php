<?php

namespace Database\Seeders;

use App\Models\MeetingTemplate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MeetingTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $meetings = [
            ['label' => 'Daily standup',               'sort_order' => 1],
            ['label' => 'Point d\'alignement équipe',  'sort_order' => 2],
            ['label' => 'Revue de sprint / planning',  'sort_order' => 3],
            ['label' => 'Code review',                 'sort_order' => 4],
            ['label' => 'Réunion client / stakeholder', 'sort_order' => 5],
        ];
        foreach ($meetings as $m) MeetingTemplate::updateOrCreate(['label' => $m['label']], ['sort_order' => $m['sort_order']]);
    }
}
