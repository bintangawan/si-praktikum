<x-app-layout>
    <x-slot name="header_title">
        Detail Praktikum: {{ $course->course_name }}
    </x-slot>

    {{-- Alert & Navigasi Atas --}}
    @include('courses.partials.header')

    {{-- Banner Laprak Final --}}
    @include('courses.partials.final-task')

    <div class="space-y-6">
        {{-- Sidebar Informasi Kelas --}}
        <details class="rounded-2xl border border-slate-200 bg-white p-4"><summary class="cursor-pointer text-sm font-semibold text-emerald-800">Informasi kelas dan peserta</summary>@include('courses.partials.sidebar')</details>

        {{-- Daftar Materi & Pertemuan --}}
        @include('courses.partials.meeting-list')
    </div>

</x-app-layout>

{{-- script lainnya yang berisi kode dari halaman ini berada pada folder partials --}}
