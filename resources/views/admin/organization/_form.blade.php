<label for="group">Kelompok</label>
<select id="group" name="group" required>
    @foreach (\App\Models\OrgMember::GROUPS as $key => $label)
        <option value="{{ $key }}" @selected(old('group', $item->group) === $key)>{{ $label }}</option>
    @endforeach
</select>
<p class="hint">Untuk "Kepala jurusan", yang tampil di situs adalah yang berurutan paling awal.</p>

<label for="name">Nama</label>
<input id="name" name="name" type="text" value="{{ old('name', $item->name) }}" minlength="2" maxlength="100" required>

<label for="position">Jabatan</label>
<input id="position" name="position" type="text" value="{{ old('position', $item->position) }}" minlength="2" maxlength="100" required>

<label for="tasks">Tugas <span class="hint">(opsional, untuk Tim IT)</span></label>
<textarea id="tasks" name="tasks" maxlength="600" style="min-height:100px">{{ old('tasks', $item->tasks) }}</textarea>
<p class="hint">Satu tugas per baris.</p>

<label for="sort_order">Urutan</label>
<input id="sort_order" name="sort_order" type="number" min="0" max="9999" value="{{ old('sort_order', $item->sort_order ?? 0) }}">

<input type="hidden" name="is_published" value="0">
<label class="checkbox"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $item->is_published))> Tampilkan di situs publik</label>

<div class="alert warn" style="margin-top:16px">
    Hanya nama dan jabatan yang ditampilkan, tanpa foto. Untuk pengurus siswa, pastikan sudah ada izin
    mencantumkan nama siswa di situs publik.
</div>
