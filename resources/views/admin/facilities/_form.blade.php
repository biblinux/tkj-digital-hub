<label for="name">Nama fasilitas</label>
<input id="name" name="name" type="text" value="{{ old('name', $item->name) }}" minlength="3" maxlength="120" required>

<label for="description">Deskripsi</label>
<textarea id="description" name="description" maxlength="1000" required style="min-height:120px">{{ old('description', $item->description) }}</textarea>
<p class="hint">Maksimal 1000 karakter.</p>

@include('admin.content._image', ['item' => $item, 'label' => 'Foto fasilitas'])

<label for="sort_order">Urutan</label>
<input id="sort_order" name="sort_order" type="number" min="0" max="9999" value="{{ old('sort_order', $item->sort_order ?? 0) }}">

<input type="hidden" name="is_published" value="0">
<label class="checkbox"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $item->is_published))> Tampilkan di situs publik</label>
