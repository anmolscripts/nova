<?php

declare(strict_types=1);

namespace Nova\Action;

use Nova\Application\Application;
use Nova\Http\RedirectResponse;
use Nova\Http\Request;
use Nova\Http\Response;
use Nova\Validation\ValidationException;

/**
 * Executes Nova server actions and normalizes their responses.
 */
final class ActionEngine
{
    public function __construct(private readonly Application $app)
    {
    }

    public function handle(Action $action, Request $request): Response
    {
        try {
            $result = require $action->file;

            return $this->adapt($result, $request);
        } catch (ValidationException $exception) {
            return $this->validationFailure($exception, $request);
        }
    }

    private function adapt(mixed $result, Request $request): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        if ($result instanceof ActionResult) {
            return $this->adaptActionResult($result, $request);
        }

        if (is_array($result)) {
            return $this->adaptActionResult(new ActionResult(true, $result), $request);
        }

        throw new ActionException('Action must return an action result, response, or array.');
    }

    private function adaptActionResult(ActionResult $result, Request $request): Response
    {
        if ($request->expectsJson()) {
            return json([
                'ok' => $result->ok,
                'data' => $result->data,
                'message' => $result->message,
            ], $result->status);
        }

        if ($result->ok) {
            flash('success', $result->message ?? 'Action completed successfully.');
        } else {
            flash('errors', $result->data);
            app()->session()->put('_old_input', $request->all());
        }

        return new RedirectResponse($result->redirectTo ?? back_url(), $result->ok ? 302 : 303);
    }

    private function validationFailure(ValidationException $exception, Request $request): Response
    {
        if ($request->expectsJson()) {
            return json([
                'ok' => false,
                'errors' => $exception->errors,
                'message' => 'Validation failed.',
            ], 422);
        }

        app()->session()->put('_old_input', $request->all());
        flash('errors', $exception->errors);

        return new RedirectResponse(back_url(), 303);
    }
}
