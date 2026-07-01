<?php

declare(strict_types=1);

namespace Nova\View;

use Nova\Http\Response;

final class ViewResponse extends Response
{
    public function __construct(private readonly ViewFactory $view, private readonly string $name, private readonly array $data = [], int $status = 200)
    {
        parent::__construct('', $status, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public function content(): string
    {
        return $this->view->render($this->name, $this->data);
    }

    public function send(): void
    {
        $this->setContent($this->content());
        parent::send();
    }
}
