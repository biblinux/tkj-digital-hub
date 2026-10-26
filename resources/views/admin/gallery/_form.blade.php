<label for="caption">Keterangan foto</label>
<input id="caption" name="caption" type="text" value="{{ old('caption', $item->caption) }}" minlength="3" maxlength="150" required>

@include('admin.content._image', ['item' => $item, 'label' => 'Foto', 'required' => true, 'removable' => false])

<label for="sort_order">Urutan</label>
<input id="sort_order" name="sort_order" type="number" min="0" max="9999" value="{{ old('sort_order', $item->sort_order ?? 0) }}">
<p class="hint">Angka kecil tampil lebih dulu. Foto dengan urutan sama diurutkan dari yang terbaru.</p>

<input type="hidden" name="is_published" value="0">
<label class="checkbox"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $item->is_published))> Tampilkan di situs publik</label>
