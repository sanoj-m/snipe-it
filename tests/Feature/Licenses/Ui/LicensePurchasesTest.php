<?php

namespace Tests\Feature\Licenses\Ui;

use App\Models\License;
use App\Models\LicensePurchase;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LicensePurchasesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_create_page_renders()
    {
        $license = License::factory()->create();

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('licenses.purchases.create', $license))
            ->assertOk();
    }

    public function test_create_page_requires_permission()
    {
        $license = License::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('licenses.purchases.create', $license))
            ->assertForbidden();
    }

    public function test_edit_page_renders()
    {
        $license = License::factory()->create();
        $purchase = LicensePurchase::create([
            'license_id' => $license->id,
            'purchase_date' => '2026-01-15',
            'amount_aed' => 1200,
        ]);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('licenses.purchases.edit', [$license, $purchase]))
            ->assertOk();
    }

    public function test_purchase_can_be_stored()
    {
        $license = License::factory()->create();

        $response = $this->actingAs(User::factory()->superuser()->create())
            ->post(route('licenses.purchases.store', $license), [
                'purchase_date' => '2026-01-15',
                'period_start' => '2026-01-15',
                'period_end' => '2027-01-14',
                'term_months' => 12,
                'amount' => 500,
                'currency' => 'USD',
                'amount_aed' => 1836.25,
                'ai_amount_aed' => 300,
                'notes' => 'Annual renewal',
            ]);

        $response->assertRedirect(route('licenses.show', $license->id));

        $purchase = LicensePurchase::where('license_id', $license->id)->sole();
        $this->assertEquals('2026-01-15', $purchase->purchase_date->format('Y-m-d'));
        $this->assertEquals(12, $purchase->term_months);
        $this->assertEquals(1836.25, $purchase->amount_aed);
        $this->assertEquals(300, $purchase->ai_amount_aed);
    }

    public function test_purchase_store_requires_permission()
    {
        $license = License::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('licenses.purchases.store', $license), [
                'purchase_date' => '2026-01-15',
            ])
            ->assertForbidden();

        $this->assertEquals(0, LicensePurchase::count());
    }

    public function test_purchase_store_requires_purchase_date()
    {
        $license = License::factory()->create();

        $this->actingAs(User::factory()->superuser()->create())
            ->from(route('licenses.purchases.create', $license))
            ->post(route('licenses.purchases.store', $license), [
                'amount_aed' => 100,
            ])
            ->assertSessionHasErrors(['purchase_date']);
    }

    public function test_purchase_store_rejects_ai_portion_above_total()
    {
        $license = License::factory()->create();

        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('licenses.purchases.store', $license), [
                'purchase_date' => '2026-01-15',
                'amount_aed' => 100,
                'ai_amount_aed' => 150,
            ])
            ->assertSessionHasErrors(['ai_amount_aed']);
    }

    public function test_purchase_store_rejects_period_end_before_start()
    {
        $license = License::factory()->create();

        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('licenses.purchases.store', $license), [
                'purchase_date' => '2026-01-15',
                'period_start' => '2026-06-01',
                'period_end' => '2026-01-01',
            ])
            ->assertSessionHasErrors(['period_end']);
    }

    public function test_purchase_can_be_updated()
    {
        $license = License::factory()->create();
        $purchase = LicensePurchase::create([
            'license_id' => $license->id,
            'purchase_date' => '2026-01-15',
            'amount_aed' => 100,
        ]);

        $this->actingAs(User::factory()->superuser()->create())
            ->put(route('licenses.purchases.update', [$license, $purchase]), [
                'purchase_date' => '2026-02-01',
                'amount_aed' => 250,
            ])
            ->assertRedirect(route('licenses.show', $license->id));

        $this->assertEquals(250, $purchase->fresh()->amount_aed);
    }

    public function test_purchase_can_be_deleted()
    {
        $license = License::factory()->create();
        $purchase = LicensePurchase::create([
            'license_id' => $license->id,
            'purchase_date' => '2026-01-15',
        ]);

        $this->actingAs(User::factory()->superuser()->create())
            ->delete(route('licenses.purchases.destroy', [$license, $purchase]))
            ->assertRedirect(route('licenses.show', $license->id));

        $this->assertSoftDeleted($purchase);
    }

    public function test_purchase_from_another_license_is_not_reachable()
    {
        $license = License::factory()->create();
        $other = License::factory()->create();
        $purchase = LicensePurchase::create([
            'license_id' => $other->id,
            'purchase_date' => '2026-01-15',
        ]);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('licenses.purchases.edit', [$license, $purchase]))
            ->assertNotFound();
    }

    public function test_invoice_upload_and_download()
    {
        $license = License::factory()->create();

        $this->actingAs(User::factory()->superuser()->create())
            ->post(route('licenses.purchases.store', $license), [
                'purchase_date' => '2026-01-15',
                'amount_aed' => 100,
                'invoice' => UploadedFile::fake()->create('invoice.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('licenses.show', $license->id));

        $purchase = LicensePurchase::where('license_id', $license->id)->sole();
        $this->assertNotNull($purchase->invoice_file);
        Storage::disk('local')->assertExists($purchase->invoice_file);

        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('licenses.purchases.invoice', [$license, $purchase]))
            ->assertOk();
    }

    public function test_invoice_download_requires_view_permission()
    {
        $license = License::factory()->create();
        $purchase = LicensePurchase::create([
            'license_id' => $license->id,
            'purchase_date' => '2026-01-15',
            'invoice_file' => 'private_uploads/licenses/test.pdf',
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('licenses.purchases.invoice', [$license, $purchase]))
            ->assertForbidden();
    }
}
