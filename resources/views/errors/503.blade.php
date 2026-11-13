@extends('errors._branded')
@section('title', 'Em manutenção')
@section('head')<meta http-equiv="refresh" content="60">@endsection
@section('icon', '🛠️')
@section('heading', 'Estamos atualizando a Vivensi')
@section('message', 'Em poucos minutos estaremos de volta, com tudo o que você já cadastrou. Esta página se atualiza sozinha.')
@section('actions')
    <a class="btn" href="{{ url()->current() }}">Tentar agora</a>
@endsection
