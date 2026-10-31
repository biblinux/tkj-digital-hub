<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\GalleryItem;
use App\Models\NewsPost;
use App\Models\OrgMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContentCmsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function news(array $o = []): NewsPost
    {
        $n = new NewsPost($o + [
            'title' => 'Praktik instalasi jaringan', 'category' => 'Kegiatan',
            'summary' => 'Ringkasan berita praktik siswa.', 'status' => 'published',
            'published_at' => now()->subDay(),
        ]);
        $n->save();

        return $n;
    }

    private function newsPayload(array $o = []): array
    {
        return $o + [
            'title' => 'Lomba kompetensi siswa', 'category' => 'Prestasi',
            'summary' => 'Siswa TKJ mengikuti lomba tingkat kabupaten.',
            'status' => 'published', 'published_at' => now()->toDateString(),
        ];
    }

    // ---- Akses ----

    public function test_guests_are_sent_to_login(): void
    {
        foreach (['/admin/news', '/admin/gallery', '/admin/facilities', '/admin/organization', '/admin/news/create'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
        $this->post('/admin/news', $this->newsPayload())->assertRedirect('/login');
    }

    public function test_users_without_permission_get_403_on_every_content_route(): void
    {
        $item = $this->news();
        $others = [$this->makeUser(User::ROLE_STUDENT), $this->makeUser(User::ROLE_TECHNICIAN), $this->makeAdmin([])];

        foreach ($others as $user) {
            $this->actingAs($user)->get('/admin/news')->assertForbidden();
            $this->actingAs($user)->get('/admin/gallery')->assertForbidden();
            $this->actingAs($user)->get('/admin/facilities')->assertForbidden();
            $this->actingAs($user)->get('/admin/organization')->assertForbidden();
            $this->actingAs($user)->post('/admin/news', $this->newsPayload())->assertForbidden();
            $this->actingAs($user)->put("/admin/news/{$item->id}", $this->newsPayload())->assertForbidden();
            $this->actingAs($user)->delete("/admin/news/{$item->id}")->assertForbidden();
        }

        $this->assertDatabaseCount('news_posts', 1);
        $this->assertSame('Praktik instalasi jaringan', $item->fresh()->title);
    }

    public function test_admin_with_permission_can_open_all_content_pages(): void
    {
        $admin = $this->makeAdmin();

        foreach (['/admin/news', '/admin/news/create', '/admin/gallery', '/admin/gallery/create',
            '/admin/facilities', '/admin/facilities/create', '/admin/organization', '/admin/organization/create'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_inactive_admin_is_blocked(): void
    {
        $admin = $this->makeAdmin();
        $admin->is_active = false;
        $admin->save();

        $this->actingAs($admin)->get('/admin/news')->assertRedirect();
    }

    // ---- Berita ----

    public function test_admin_creates_news_and_it_records_the_author(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post('/admin/news', $this->newsPayload())->assertRedirect('/admin/news');

        $this->assertDatabaseHas('news_posts', ['title' => 'Lomba kompetensi siswa', 'created_by' => $admin->id, 'status' => 'published']);
    }

    public function test_news_validation_rejects_bad_input(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post('/admin/news', ['title' => 'abc', 'category' => 'Ngawur', 'summary' => 'pendek', 'status' => 'x'])
            ->assertSessionHasErrors(['title', 'category', 'summary', 'status']);

        $this->assertDatabaseCount('news_posts', 0);
    }

    public function test_news_cannot_mass_assign_author_or_image_path(): void
    {
        $admin = $this->makeAdmin();
        $other = $this->makeUser();

        $this->actingAs($admin)->post('/admin/news', $this->newsPayload(['created_by' => $other->id, 'image_path' => 'content/news/palsu.jpg']));

        $this->assertDatabaseHas('news_posts', ['created_by' => $admin->id, 'image_path' => null]);
    }

    public function test_published_news_without_date_gets_todays_date(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post('/admin/news', $this->newsPayload(['published_at' => '']));

        $this->assertSame(now()->toDateString(), NewsPost::first()->published_at->toDateString());
    }

    public function test_admin_updates_and_deletes_news(): void
    {
        $admin = $this->makeAdmin();
        $n = $this->news();

        $this->actingAs($admin)->put("/admin/news/{$n->id}", $this->newsPayload(['title' => 'Judul sudah diperbarui']))->assertRedirect('/admin/news');
        $this->assertSame('Judul sudah diperbarui', $n->fresh()->title);
        $this->assertSame($admin->id, $n->fresh()->updated_by);

        $this->actingAs($admin)->delete("/admin/news/{$n->id}")->assertRedirect('/admin/news');
        $this->assertDatabaseCount('news_posts', 0);
    }

    public function test_news_index_filters_and_escapes_html(): void
    {
        $admin = $this->makeAdmin();
        $this->news(['title' => '<script>alert(1)</script> berita']);
        $this->news(['title' => 'Berita draf saja', 'status' => 'draft']);

        $res = $this->actingAs($admin)->get('/admin/news')->assertOk();
        $this->assertStringNotContainsString('<script>alert(1)</script>', $res->getContent());
        $this->assertStringContainsString('&lt;script&gt;', $res->getContent());

        $this->actingAs($admin)->get('/admin/news?status=draft')->assertSee('Berita draf saja')->assertDontSee('berita</a>');
    }

    // ---- Foto ----

    public function test_news_image_is_stored_with_random_name_and_removed_on_delete(): void
    {
        $admin = $this->makeAdmin();
        $file = UploadedFile::fake()->image('foto-asli-pengguna.jpg', 800, 600);

        $this->actingAs($admin)->post('/admin/news', $this->newsPayload(['image' => $file]))->assertRedirect('/admin/news');

        $n = NewsPost::first();
        $this->assertNotNull($n->image_path);
        $this->assertStringStartsWith('content/news/', $n->image_path);
        $this->assertStringNotContainsString('foto-asli-pengguna', $n->image_path);
        Storage::disk('public')->assertExists($n->image_path);

        $path = $n->image_path;
        $this->actingAs($admin)->delete("/admin/news/{$n->id}");
        Storage::disk('public')->assertMissing($path);
    }

    public function test_replacing_and_removing_an_image_deletes_the_old_file(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin)->post('/admin/news', $this->newsPayload(['image' => UploadedFile::fake()->image('a.jpg', 400, 300)]));
        $n = NewsPost::first();
        $old = $n->image_path;

        $this->actingAs($admin)->put("/admin/news/{$n->id}", $this->newsPayload(['image' => UploadedFile::fake()->image('b.png', 400, 300)]));
        $new = $n->fresh()->image_path;
        $this->assertNotSame($old, $new);
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($new);

        $this->actingAs($admin)->put("/admin/news/{$n->id}", $this->newsPayload(['remove_image' => '1']));
        $this->assertNull($n->fresh()->image_path);
        Storage::disk('public')->assertMissing($new);
    }

    public function test_dangerous_or_oversized_uploads_are_rejected(): void
    {
        $admin = $this->makeAdmin();

        $bad = [
            'svg' => UploadedFile::fake()->createWithContent('x.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
            'php' => UploadedFile::fake()->createWithContent('shell.php', '<?php echo 1;'),
            'php-gambar' => UploadedFile::fake()->createWithContent('shell.jpg', '<?php system($_GET["c"]);'),
            'besar' => UploadedFile::fake()->image('besar.jpg', 100, 100)->size(6000),
        ];

        foreach ($bad as $name => $file) {
            $this->actingAs($admin)->post('/admin/news', $this->newsPayload(['image' => $file]))
                ->assertSessionHasErrors('image');
        }

        $this->assertDatabaseCount('news_posts', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_validation_messages_are_translated_not_raw_keys(): void
    {
        $file = UploadedFile::fake()->createWithContent('x.svg', '<svg/>');
        $rules = [
            'f' => ['image'], 'g' => ['mimes:jpg,png'], 'h' => ['file', 'max:1'], 'd' => ['date'],
            'a' => ['date', 'after:2999-01-01'], 'i' => ['in:x,y'], 'r' => ['required'], 'b' => ['boolean'],
            'n' => ['numeric'], 'u' => ['url'], 's' => ['size:3'], 'm' => ['min:10'], 'x' => ['max:2'],
        ];
        $data = ['f' => $file, 'g' => $file, 'h' => UploadedFile::fake()->create('b.jpg', 50), 'd' => 'bukan-tanggal',
            'a' => '2020-01-01', 'i' => 'z', 'b' => 'mungkin', 'n' => 'abc', 'u' => 'bukan url', 's' => 'ab', 'm' => 'ab', 'x' => 'abcdef'];

        $errors = \Illuminate\Support\Facades\Validator::make($data, $rules)->errors()->all();

        $this->assertNotEmpty($errors);
        foreach ($errors as $message) {
            $this->assertStringNotContainsString('validation.', $message);
        }
    }

    // ---- Galeri, fasilitas, organisasi ----

    public function test_gallery_requires_an_image_and_stores_it(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post('/admin/gallery', ['caption' => 'Praktik jaringan'])->assertSessionHasErrors('image');

        $this->actingAs($admin)->post('/admin/gallery', [
            'caption' => 'Praktik jaringan', 'image' => UploadedFile::fake()->image('g.jpg', 600, 400), 'is_published' => '1',
        ])->assertRedirect('/admin/gallery');

        $g = GalleryItem::first();
        $this->assertTrue($g->is_published);
        Storage::disk('public')->assertExists($g->image_path);

        $path = $g->image_path;
        $this->actingAs($admin)->delete("/admin/gallery/{$g->id}");
        Storage::disk('public')->assertMissing($path);
    }

    public function test_facility_crud(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post('/admin/facilities', [
            'name' => 'Laboratorium Jaringan', 'description' => 'Ruang praktik jaringan dengan 20 komputer.', 'is_published' => '1',
        ])->assertRedirect('/admin/facilities');

        $f = Facility::first();
        $this->actingAs($admin)->put("/admin/facilities/{$f->id}", [
            'name' => 'Lab Jaringan Baru', 'description' => 'Deskripsi diperbarui untuk laboratorium.', 'is_published' => '1',
        ])->assertRedirect('/admin/facilities');
        $this->assertSame('Lab Jaringan Baru', $f->fresh()->name);

        $this->actingAs($admin)->delete("/admin/facilities/{$f->id}");
        $this->assertDatabaseCount('facilities', 0);
    }

    public function test_organization_member_crud_and_group_validation(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post('/admin/organization', ['group' => 'ngawur', 'name' => 'Budi', 'position' => 'Ketua'])
            ->assertSessionHasErrors('group');

        $this->actingAs($admin)->post('/admin/organization', [
            'group' => 'it_team', 'name' => 'Budi Santoso', 'position' => 'Administrator Jaringan',
            'tasks' => "Memantau jaringan\nMenangani gangguan", 'is_published' => '1',
        ])->assertRedirect('/admin/organization');

        $m = OrgMember::first();
        $this->assertSame(['Memantau jaringan', 'Menangani gangguan'], $m->taskList());

        $this->actingAs($admin)->delete("/admin/organization/{$m->id}");
        $this->assertDatabaseCount('org_members', 0);
    }

    // ---- API publik ----

    public function test_public_content_api_returns_only_published_visible_items(): void
    {
        $this->news(['title' => 'Berita terbit']);
        $this->news(['title' => 'Berita masih draf', 'status' => 'draft']);
        $this->news(['title' => 'Berita terjadwal', 'published_at' => now()->addWeek()]);

        $g = new GalleryItem(['caption' => 'Foto tayang', 'is_published' => true]);
        $g->image_path = 'content/gallery/a.jpg';
        $g->save();
        $h = new GalleryItem(['caption' => 'Foto disembunyikan', 'is_published' => false]);
        $h->image_path = 'content/gallery/b.jpg';
        $h->save();

        $res = $this->getJson('/api/v1/content')->assertOk();

        $res->assertJsonCount(1, 'news')->assertJsonPath('news.0.title', 'Berita terbit');
        $res->assertJsonCount(1, 'gallery')->assertJsonPath('gallery.0.caption', 'Foto tayang');
        $this->assertStringContainsString('/storage/content/gallery/a.jpg', $res->json('gallery.0.src'));
        $this->assertStringNotContainsString('draf', $res->getContent());
        $this->assertStringNotContainsString('terjadwal', $res->getContent());
        $this->assertStringNotContainsString('disembunyikan', $res->getContent());
    }

    public function test_public_content_api_hides_internal_fields_and_groups_org(): void
    {
        $admin = $this->makeAdmin();
        $this->news(['title' => 'Berita terbit']);

        foreach ([['head', 'Kepala Jurusan', 'Pak Kepala'], ['teachers', 'Guru produktif', 'Bu Guru'], ['students', 'Ketua', 'Siswa Ketua']] as [$grp, $pos, $name]) {
            $m = new OrgMember(['group' => $grp, 'name' => $name, 'position' => $pos, 'is_published' => true]);
            $m->save();
        }
        $t = new OrgMember(['group' => 'it_team', 'name' => 'Admin Jaringan', 'position' => 'Administrator', 'tasks' => "Tugas satu\nTugas dua", 'is_published' => true]);
        $t->save();
        $hidden = new OrgMember(['group' => 'teachers', 'name' => 'Tidak Tampil', 'position' => 'X', 'is_published' => false]);
        $hidden->save();

        $res = $this->getJson('/api/v1/content')->assertOk();

        $res->assertJsonPath('org.head.name', 'Pak Kepala')
            ->assertJsonCount(1, 'org.teachers')->assertJsonCount(1, 'org.students')
            ->assertJsonPath('it_team.0.tasks', ['Tugas satu', 'Tugas dua']);

        $body = $res->getContent();
        foreach (['created_by', 'updated_by', 'status', 'image_path', 'email', 'Tidak Tampil', (string) $admin->email] as $secret) {
            $this->assertStringNotContainsString($secret, $body, $secret);
        }
    }

    public function test_public_content_api_is_empty_but_valid_with_no_data(): void
    {
        $this->getJson('/api/v1/content')->assertOk()
            ->assertJson(['news' => [], 'gallery' => [], 'facilities' => [], 'org' => ['head' => null, 'teachers' => [], 'students' => []], 'it_team' => []])
            ->assertHeader('Cache-Control');
    }
}
