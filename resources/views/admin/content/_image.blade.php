@php($required = $required ?? false)
@php($removable = $removable ?? true)
<label for="image">{{ $label ?? 'Foto' }} @unless ($required)<span class="hint">(opsional)</span>@endunless</label>
@if ($item->image_path)
    <p><img src="{{ $item->imageUrl() }}" alt="Foto saat ini" style="max-width:240px;max-height:180px;border-radius:8px;border:1px solid var(--line)"></p>
@endif
<input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" @required($required && ! $item->image_path)>
<p class="hint">
    JPG, PNG, atau WebP, maksimal 2 MB (16 megapiksel).
    @if (extension_loaded('gd'))
        Foto dikecilkan otomatis dan data lokasi (GPS) di dalamnya dibuang.
    @else
        Server ini belum bisa memproses foto, jadi hapus data lokasi (GPS) dari foto sebelum mengunggah.
    @endif
</p>
@if ($item->image_path && $removable)
    <label class="checkbox"><input type="checkbox" name="remove_image" value="1"> Hapus foto saat ini</label>
@endif
