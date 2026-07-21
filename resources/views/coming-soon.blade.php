@extends('layouts.app')
@section('title', $title.' — GlowSkin')
@section('content')
<main class="pt-28 min-h-screen bg-black flex items-center justify-center px-4"><div class="text-center"><p class="text-xs uppercase tracking-[.35em] mb-3" style="color:var(--primary)">PAGE READY FOR NEXT MODULE</p><h1 class="text-4xl md:text-6xl font-extrabold uppercase text-white">{{ $title }}</h1><p class="text-white/50 mt-4">Route sudah dibuat di Laravel, tinggal lanjut isi modulnya.</p><a href="{{ route('home') }}" class="green-gradient-btn inline-flex mt-8 px-8 py-4 font-bold uppercase tracking-widest">Back Home</a></div></main>
@endsection
