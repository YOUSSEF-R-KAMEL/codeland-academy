<?php

namespace Database\Seeders;

use App\Models\FeedbackTemplate;
use Illuminate\Database\Seeder;

class FeedbackTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            1 => [
                'أحسنت المحاولة! استمر في التعلم والتدريب.',
                'بداية جيدة، ومع المزيد من الممارسة هتتحسن.',
            ],
            2 => [
                'بداية مشجعة! حاول تراجع الدرس وتطوّر مشروعك.',
                'عندك أفكار حلوة، محتاج بس تهتم ببعض التفاصيل.',
            ],
            3 => [
                'عمل جيد! عندك فهم كويس، استمر في التطوير.',
                'مجهود واضح! حاول تضيف لمسات جديدة لمشروعك.',
            ],
            4 => [
                'شغل رائع! عندك تقدم واضح، واصل التطور.',
                'أداء مميز! فاضلك خطوات بسيطة للإتقان.',
            ],
            5 => [
                'ممتاز! أبدعت في تنفيذ المشروع، استمر يا بطل!',
                'إبداع حقيقي! مشروع مميز وفخورين بمجهودك.',
            ],
        ];

        foreach ($templates as $rating => $comments) {
            foreach ($comments as $index => $comment) {
                FeedbackTemplate::updateOrCreate(
                    [
                        'rating' => $rating,
                        'comment' => $comment,
                    ],
                    [
                        'is_active' => true,
                        'sort_order' => $index + 1,
                    ]
                );
            }
        }
    }
}
