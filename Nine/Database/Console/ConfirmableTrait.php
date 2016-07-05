<?php

namespace Illuminate\Console;

use Closure;

trait ConfirmableTrait
{
    /**
     * Confirm before proceeding with the action.
     *
     * @param  string             $warning
     * @param  \Closure|bool|null $callback
     *
     * @return bool
     */
    public function confirmToProceed($warning = 'Application In Production!', $callback = NULL)
    {
        $callback = is_null($callback) ? $this->getDefaultConfirmCallback() : $callback;

        $shouldConfirm = $callback instanceof Closure ? call_user_func($callback) : $callback;

        if ($shouldConfirm) {
            /** @var Command $this */
            if ($this->option('force')) {
                return TRUE;
            }

            $this->comment(str_repeat('*', strlen($warning) + 12));
            $this->comment('*     ' . $warning . '     *');
            $this->comment(str_repeat('*', strlen($warning) + 12));
            $this->getOutput()->writeln('');

            $confirmed = $this->confirm('Do you really wish to run this command?');

            if ( ! $confirmed) {
                $this->comment('Command Cancelled!');

                return FALSE;
            }
        }

        return TRUE;
    }

    /**
     * Get the default confirmation callback.
     *
     * @return \Closure
     */
    protected function getDefaultConfirmCallback()
    {
        return function () {
            return strtoupper(env('APP_ENV')) === 'PRODUCTION';
            //return $this->getLaravel()->environment() === 'production';
        };
    }
}
