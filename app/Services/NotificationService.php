<?php

namespace App\Services;

use App\Contracts\NotificationChannel;
use App\Models\User;
use App\Services\Channels\EmailChannel;
use App\Services\Channels\SmsChannel;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    protected array $channels = [];

    public function __construct()
    {
        $this->channels = [
            'email' => new EmailChannel(),
            'sms'   => new SmsChannel(),
        ];
    }

    public function channel(string $name): NotificationChannel
    {
        if (! isset($this->channels[$name])) {
            throw new \InvalidArgumentException("Notification channel [{$name}] is not registered.");
        }

        return $this->channels[$name];
    }

    public function send(string $channelName, string $to, string $subject, string $body, array $options = []): bool
    {
        return $this->channel($channelName)->send($to, $subject, $body, $options);
    }

    public function sendEmail(string $to, string $subject, string $body, array $options = []): bool
    {
        return $this->send('email', $to, $subject, $body, $options);
    }

    // ──────────────────────────────────────────────────────
    //  Template-based helpers
    // ──────────────────────────────────────────────────────

    public function sendWelcome(User $user): bool
    {
        $html = $this->renderTemplate('emails.welcome', [
            'userName' => $user->name,
        ]);

        return $this->sendEmail($user->email, 'Welcome to ' . config('app.name'), $html, [
            'template' => 'welcome',
            'metadata' => ['user_id' => $user->id],
        ]);
    }

    public function sendPasswordChanged(User $user): bool
    {
        $html = $this->renderTemplate('emails.password-reset', [
            'userName' => $user->name,
        ]);

        return $this->sendEmail($user->email, 'Password Changed', $html, [
            'template' => 'password_changed',
            'metadata' => ['user_id' => $user->id],
        ]);
    }

    public function sendTransactionAlert(User $user, float $amount, string $type, string $accountName): bool
    {
        $html = $this->renderTemplate('emails.transaction-alert', [
            'userName'    => $user->name,
            'amount'      => number_format($amount, 2),
            'type'        => ucfirst($type),
            'accountName' => $accountName,
            'currency'    => $user->currency ?? 'INR',
        ]);

        return $this->sendEmail($user->email, 'Large Transaction Alert', $html, [
            'template' => 'transaction_alert',
            'metadata' => ['user_id' => $user->id, 'amount' => $amount, 'type' => $type],
        ]);
    }

    public function sendBudgetExceeded(User $user, string $categoryName, float $budgetAmount, float $spent): bool
    {
        $html = $this->renderTemplate('emails.budget-exceeded', [
            'userName'     => $user->name,
            'categoryName' => $categoryName,
            'budgetAmount' => number_format($budgetAmount, 2),
            'spent'        => number_format($spent, 2),
            'currency'     => $user->currency ?? 'INR',
        ]);

        return $this->sendEmail($user->email, 'Budget Exceeded Alert', $html, [
            'template' => 'budget_exceeded',
            'metadata' => ['user_id' => $user->id, 'category' => $categoryName, 'budget' => $budgetAmount, 'spent' => $spent],
        ]);
    }

    public function sendAccountDeactivated(User $user): bool
    {
        $html = $this->renderTemplate('emails.account-deactivated', [
            'userName' => $user->name,
        ]);

        return $this->sendEmail($user->email, 'Account Deactivated', $html, [
            'template' => 'account_deactivated',
            'metadata' => ['user_id' => $user->id],
        ]);
    }

    protected function renderTemplate(string $view, array $data = []): string
    {
        $data['appName'] = config('app.name', 'Finance Tracker');
        $data['year']    = date('Y');

        return view($view, $data)->render();
    }
}
