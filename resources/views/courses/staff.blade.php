<x-app-layout>
    <x-slot name="header_title">Penugasan kelas {{ $course->course_name }}</x-slot>
    <form method="POST" action="{{ route('courses.staff.update', $course) }}" class="max-w-xl space-y-5 rounded-2xl border border-slate-200 bg-white p-6">@csrf @method('PUT')
        <p class="text-sm leading-6 text-slate-500">Petugas baru mendapat akses kelas. Riwayat ACC dan nama pemeriksa sebelumnya tetap disimpan.</p>
        @foreach(['dosen' => $dosens, 'aslab' => $aslabs] as $role => $users)
            <label class="block text-sm font-semibold">{{ ucfirst($role) }}<select name="{{ $role }}_id" class="mt-2 w-full rounded-xl border-slate-200">@foreach($users as $user)<option value="{{ $user->id }}" @selected(old($role.'_id', $course->{$role.'_id'}) === $user->id)>{{ $user->name }} — {{ $user->id }}</option>@endforeach</select></label><x-input-error :messages="$errors->get($role.'_id')" />
        @endforeach
        <button class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-semibold text-white">Simpan penugasan</button>
    </form>
</x-app-layout>
