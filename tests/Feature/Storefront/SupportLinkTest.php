<?php

namespace Tests\Feature\Storefront;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportLinkTest extends TestCase
{
    use RefreshDatabase;

    private const LINK = '<a class="%s" href="https://t.me/CECELECTRONICS" target="_blank" rel="noopener"';

    public function test_account_dashboard_support_card_opens_telegram(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('account.dashboard'))
            ->assertOk()
            ->assertSee(sprintf(self::LINK, 'panel service'), false)
            ->assertSee('Telegram @CECELECTRONICS or call 012 220 152.');
    }

    public function test_register_page_support_card_opens_telegram(): void
    {
        $this->get(route('customer.register'))
            ->assertOk()
            ->assertSee(sprintf(self::LINK, 'service'), false);
    }
}
