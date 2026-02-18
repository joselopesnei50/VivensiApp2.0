@extends('layouts.app')
@section('content')
<h1>DEBUG MODE: HELLO WORLD</h1>
<p>If you see this, the Controller is fine.</p>
<p>Post Title: {{ $post->title }}</p>
@endsection
