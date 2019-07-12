<?php

namespace App\Jobs\Trial;

use App\Http\Modules\RichContentService;
use App\Jobs\Job;
use App\Models\Pin;

class PinTrial extends Job
{
    protected $pin;
    /**
     * 0 => 帖子创建
     * 1 => 更新帖子
     */
    protected $type;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Pin $pin, $type)
    {
        $this->pin = $pin;
        $this->type = $type;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $pin = $this->pin;
        $content = $pin->content;

        $richContentService = new RichContentService();

        $risk = $richContentService->detectContentRisk($content);

        if ($risk['risk_score'] > 0)
        {
            $pin->deletePin(2);
        }
        else if ($risk['use_review'] > 0)
        {
            $pin->reviewPin(1);
        }
        else
        {
            $pin->reflowPin();
        }

        return;
    }
}
