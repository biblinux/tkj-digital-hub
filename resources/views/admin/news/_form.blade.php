<label for="title">Judul</label>
<input id="title" name="title" type="text" value="{{ old('title', $item->title) }}" minlength="5" maxlength="150" required>

<label for="category">Kategori</label>
<select id="category" name="category" required>
    @foreach (\App\Models\NewsPost::CATEGORIES as $category)
        <option value="{{ $category }}" @selected(old('category', $item->category) === $category)>{{ $category }}</option>
    @endforeach
</select>

<label for="summary">Ringkasan</label>
<textarea id="summary" name="summary" maxlength="300" required style="min-height:90px">{{ old('summary', $item->summary) }}</textarea>
<p class="hint">Maksimal 300 karakter. Tampil di daftar berita.</p>

<label for="body">Isi berita <span class="hint">(opsional)</span></label>
<textarea id="body" name="body" maxlength="10000" style="min-height:220px">{{ old('body', $item->body) }}</textarea>
<p class="hint">Pisahkan paragraf dengan satu baris kosong. HTML tidak didukung.</p>

<label for="status">Status</label>
<select id="status" name="status" required>
    @foreach (\App\Models\NewsPost::STATUSES as $key => $label)
        <option value="{{ $key }}" @selected(old('status', $item->status) === $key)>{{ $label }}</option>
    @endforeach
</select>

<label for="published_at">Tanggal tayang</label>
<input id="published_at" name="published_at" type="date" value="{{ old('published_at', $item->published_at?->format('Y-m-d')) }}">
<p class="hint">Berita terbit baru tampil di situs publik pada tanggal ini. Kosongkan untuk memakai hari ini.</p>

@include('admin.content._image', ['item' => $item, 'label' => 'Foto berita'])
