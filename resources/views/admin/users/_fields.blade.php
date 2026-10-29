<label for="name">Nama lengkap</label>
<input id="name" name="name" type="text" value="{{ old('name', $user?->name) }}" maxlength="100" required>

<label for="username">Username</label>
<input id="username" name="username" type="text" value="{{ old('username', $user?->username) }}"
       minlength="3" maxlength="30" pattern="[A-Za-z0-9._-]+" required>
<p class="hint">Huruf, angka, titik, garis bawah, atau tanda hubung.</p>

<label for="email">Email</label>
<input id="email" name="email" type="email" value="{{ old('email', $user?->email) }}" maxlength="255" required>

<label for="class_name">Kelas (opsional)</label>
<input id="class_name" name="class_name" type="text" value="{{ old('class_name', $user?->class_name) }}" maxlength="30">
<p class="hint">Contoh: X TKJ 1. Tampil di direktori siswa.</p>

@if ($user)
    <input type="hidden" name="show_in_directory" value="0">
    <label class="checkbox"><input type="checkbox" name="show_in_directory" value="1" @checked(old('show_in_directory', $user->show_in_directory))> Tampilkan di situs publik (direktori siswa atau teknisi)</label>
@endif

@if (! $user || ! $user->isSuperAdmin())
    <label for="role">Peran</label>
    <select id="role" name="role" required>
        @foreach ($roles as $key => $label)
            <option value="{{ $key }}" @selected(old('role', $user?->role) === $key)>{{ $label }}</option>
        @endforeach
    </select>
@endif
