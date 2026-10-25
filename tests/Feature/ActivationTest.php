<?php

namespace Tests\Feature;

use App\Mail\AccountActivationMail;
use App\Models\AccountActivation;
use App\Models\User;
use App\Services\AccountActivationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ActivationTest extends TestCase
{
    use RefreshDatabase;

    private const STRONG = 'Sandi-Kuat-2026!x';

    /** @return array{0: User, 1: string} */
    private function pendingUser(): array
    {
        $user = $this->makeUser(User::ROLE_STUDENT, false, ['username' => 'rina', 'email' => 'rina@example.com']);
        $token = app(AccountActivationService::class)->issue($user);

        return [$user, $token];
    }

    private function activate(string $token, string $password = self::STRONG)
    {
        return $this->post(route('activation.activate', $token), [
            'password' => $password,
            'password_confirmation' => $password,
        ]);
    }

    public function test_valid_token_shows_form(): void
    {
        [, $token] = $this->pendingUser();

        $this->get(route('activation.show', $token))
            ->assertOk()
            ->assertSee('rina@example.com');
    }

    public function test_valid_token_activates_account_and_hashes_password(): void
    {
        [$user, $token] = $this->pendingUser();

        $this->activate($token)->assertRedirect(route('login'));

        $user->refresh();
        $this->assertTrue($user->is_active);
        $this->assertNotSame(self::STRONG, $user->password);
        $this->assertTrue(Hash::check(self::STRONG, $user->password));
        $this->assertNotNull($user->activation->used_at);

        // Setelah aktivasi, pengguna dapat login.
        $this->post('/login', ['login' => 'rina', 'password' => self::STRONG])
            ->assertRedirect(route('dashboard'));
    }

    public function test_database_stores_hash_not_raw_token(): void
    {
        [$user, $token] = $this->pendingUser();

        $stored = AccountActivation::where('user_id', $user->id)->value('token_hash');

        $this->assertNotSame($token, $stored);
        $this->assertSame(hash('sha256', $token), $stored);
        $this->assertSame(64, strlen($token));
    }

    public function test_invalid_token_is_rejected(): void
    {
        $this->get(route('activation.show', str_repeat('a', 64)))->assertNotFound();
        $this->get('/activate/pendek')->assertNotFound();

        $this->activate(str_repeat('b', 64))->assertRedirect(route('login'))->assertSessionHasErrors('login');
    }

    public function test_expired_token_is_rejected(): void
    {
        [$user, $token] = $this->pendingUser();

        $this->travel(61)->minutes();

        $this->get(route('activation.show', $token))->assertNotFound();
        $this->activate($token)->assertSessionHasErrors('login');
        $this->assertFalse($user->fresh()->is_active);
    }

    public function test_token_is_valid_just_before_expiry(): void
    {
        [, $token] = $this->pendingUser();

        $this->travel(59)->minutes();

        $this->get(route('activation.show', $token))->assertOk();
    }

    public function test_used_token_cannot_be_reused(): void
    {
        [$user, $token] = $this->pendingUser();

        $this->activate($token)->assertRedirect(route('login'));

        $this->get(route('activation.show', $token))->assertNotFound();

        $this->activate($token, 'Sandi-Lain-2026!yz')->assertSessionHasErrors('login');
        $this->assertTrue(Hash::check(self::STRONG, $user->fresh()->password));
    }

    public function test_weak_password_is_rejected_and_token_stays_valid(): void
    {
        [$user, $token] = $this->pendingUser();

        $this->from(route('activation.show', $token))
            ->activate($token, 'lemah')
            ->assertSessionHasErrors('password');

        $this->assertFalse($user->fresh()->is_active);
        $this->get(route('activation.show', $token))->assertOk();
    }

    public function test_password_confirmation_must_match(): void
    {
        [, $token] = $this->pendingUser();

        $this->post(route('activation.activate', $token), [
            'password' => self::STRONG,
            'password_confirmation' => 'Berbeda-2026!abc',
        ])->assertSessionHasErrors('password');
    }

    public function test_reissuing_invalidates_previous_token(): void
    {
        [$user, $old] = $this->pendingUser();

        $new = app(AccountActivationService::class)->issue($user);

        $this->assertNotSame($old, $new);
        $this->get(route('activation.show', $old))->assertNotFound();
        $this->get(route('activation.show', $new))->assertOk();
        $this->assertSame(1, AccountActivation::where('user_id', $user->id)->count());
    }

    public function test_activation_email_contains_correct_url_and_does_not_send_real_mail(): void
    {
        Mail::fake();
        $user = $this->makeUser(User::ROLE_STUDENT, false, ['email' => 'dewi@example.com']);

        $this->assertTrue(app(AccountActivationService::class)->issueAndSend($user));

        Mail::assertSent(AccountActivationMail::class, function (AccountActivationMail $mail) use ($user) {
            $this->assertTrue($mail->hasTo('dewi@example.com'));
            $this->assertMatchesRegularExpression(
                '#^'.preg_quote(url('/activate'), '#').'/[A-Za-z0-9]{64}$#',
                $mail->activationUrl
            );

            $token = basename($mail->activationUrl);
            $this->assertSame(
                hash('sha256', $token),
                AccountActivation::where('user_id', $user->id)->value('token_hash')
            );

            $mail->assertSeeInHtml($mail->activationUrl, false);

            return true;
        });
    }

    public function test_send_failure_returns_false_and_does_not_log_token(): void
    {
        $user = $this->makeUser(User::ROLE_STUDENT, false);
        $token = app(AccountActivationService::class)->issue($user);

        Mail::shouldReceive('to')->andThrow(new \RuntimeException('smtp mati '.$token));

        \Illuminate\Support\Facades\Log::spy();

        $this->assertFalse(app(AccountActivationService::class)->send($user, $token));

        \Illuminate\Support\Facades\Log::shouldHaveReceived('error')->withArgs(
            fn ($message, $context) => ! str_contains(json_encode($context), $token)
        );
    }

    public function test_already_active_account_cannot_be_reactivated_with_token(): void
    {
        $user = $this->makeUser(User::ROLE_STUDENT, true);
        $token = app(AccountActivationService::class)->issue($user);

        $this->activate($token)->assertSessionHasErrors('login');
        $this->assertFalse(Hash::check(self::STRONG, $user->fresh()->password));
    }
}
