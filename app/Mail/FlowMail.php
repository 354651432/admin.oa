<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FlowMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param $view
     * @param $data
     * @param $title
     */
    public function __construct($view, $data, $title)
    {
        if (empty($title)) {
            $title = "流程审批邮件";
        }

        $this->subject($title);
        $this->view($view, $data);
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this;
    }
}
