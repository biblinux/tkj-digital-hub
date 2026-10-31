<label for="name">Nama lengkap</label>
<input id="name" name="name" type="text" value="{{ old('name', $user?->name) }}" maxlength="100" required>

<label for="username">Username</label>
<input id="username" name="username" type="text" value="{{ old('username', $user?->username) }}"
       minlength="3" maxlength="30" pattern="[A-Za-z0-9._-]+" required>
<p class="hint">Huruf, angka, titik, garis bawah, atau tanda hubung.</p>

<label for="email">Email</label>
<input id="email" name="email" type="email" value="{{ old('email', $user?->email) }}" maxlength="255" required>

<label for="class_name">Kelas <span class="hint">(khusus siswa)</span></label>
<input id="class_name" name="class_name" type="text" value="{{ old('class_name', $user?->class_name) }}" maxlength="30" placeholder="Contoh: XI TKJ 1">
<p class="hint">Siswa yang aktif otomatis tampil di halaman Direktori Siswa dengan kelas ini.</p>

@if (! $user || ! $user->isSuperAdmin())
    <label for="role">Peran</label>
    <select id="role" name="role" required>
        @foreach ($roles as $key => $label)
            <option value="{{ $key }}" @selected(old('role', $user?->role) === $key)>{{ $label }}</option>
        @endforeach
    </select>
@endif
